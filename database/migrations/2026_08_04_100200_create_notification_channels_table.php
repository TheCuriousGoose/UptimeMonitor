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
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 50);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('monitor_notification_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Monitor::class)->constrained()->cascadeOnDelete();
            $table->foreignId('notification_channel_id')->constrained()->cascadeOnDelete();

            $table->unique(['monitor_id', 'notification_channel_id'], 'monitor_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_notification_channel');
        Schema::dropIfExists('notification_channels');
    }
};
