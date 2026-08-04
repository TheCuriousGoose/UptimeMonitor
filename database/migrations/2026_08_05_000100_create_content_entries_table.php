<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Docs, blog posts and changelog entries are the same shape — titled,
     * slugged, markdown-bodied, drafted then published — so they share one
     * table discriminated by `type` rather than three near-identical ones.
     */
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('type', 20);
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');

            // Changelog only.
            $table->string('version', 40)->nullable();
            // Docs only — groups pages in the sidebar.
            $table->string('category', 80)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // A slug only has to be unique within its own content type, so
            // /docs/getting-started and /blog/getting-started can coexist.
            $table->unique(['type', 'slug']);
            // The public readers all filter on type + published, then order.
            $table->index(['type', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};
