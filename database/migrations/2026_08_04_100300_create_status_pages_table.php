<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('slug', 60)->unique();
            $table->string('title', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('monitor_status_page', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Monitor::class)->constrained()->cascadeOnDelete();
            $table->foreignId('status_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['monitor_id', 'status_page_id'], 'monitor_status_page_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_status_page');
        Schema::dropIfExists('status_pages');
    }
};
