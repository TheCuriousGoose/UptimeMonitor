<?php

use App\Models\Monitor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Monitor::class)->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('resolved_at')->nullable();
            $table->string('cause', 255)->nullable();
            $table->unsignedInteger('failed_checks')->default(1);
            $table->timestamps();

            $table->index(['monitor_id', 'started_at']);
            $table->index(['monitor_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
