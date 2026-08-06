<?php

use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dateTime('acknowledged_at')->nullable()->after('is_maintenance');
            $table->foreignIdFor(User::class, 'acknowledged_by')->nullable()->after('acknowledged_at')
                ->constrained('users')->nullOnDelete();
        });

        // One table, not two. A private note and a public update have the same
        // shape, lifecycle, ordering and authorisation; splitting them would
        // turn "the timeline" into a union of two queries.
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Incident::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('status', 20)->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
            $table->index(['incident_id', 'is_public']);
        });

        // Opt in per page: an existing published page must not start
        // publishing its outage history the moment this deploys.
        Schema::table('status_pages', function (Blueprint $table) {
            $table->boolean('show_incidents')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('status_pages', fn (Blueprint $table) => $table->dropColumn('show_incidents'));
        Schema::dropIfExists('incident_updates');

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acknowledged_by');
            $table->dropColumn('acknowledged_at');
        });
    }
};
