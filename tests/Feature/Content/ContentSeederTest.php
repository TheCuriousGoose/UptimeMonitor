<?php

namespace Tests\Feature\Content;

use App\Enums\ContentType;
use App\Models\ContentEntry;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_legal_pages_docs_and_posts(): void
    {
        $this->seed(ContentSeeder::class);

        $this->assertTrue(
            ContentEntry::query()->ofType(ContentType::Legal)->whereIn('slug', ['privacy', 'terms'])->count() === 2,
            'Both legal pages should be seeded.',
        );
        $this->assertGreaterThanOrEqual(5, ContentEntry::query()->ofType(ContentType::Doc)->count());
        $this->assertGreaterThanOrEqual(3, ContentEntry::query()->ofType(ContentType::Post)->count());
    }

    /**
     * The legal pages exist to be rewritten with real controller details. A
     * seeder that overwrote them on the next deploy would silently republish
     * placeholders in place of a live privacy policy.
     */
    public function test_re_seeding_never_overwrites_an_edited_entry(): void
    {
        $this->seed(ContentSeeder::class);

        $privacy = ContentEntry::query()->ofType(ContentType::Legal)->where('slug', 'privacy')->sole();
        $privacy->update([
            'title' => 'Privacyverklaring',
            'body' => 'Our real, reviewed policy.',
        ]);

        $this->seed(ContentSeeder::class);

        $fresh = $privacy->fresh();

        $this->assertSame('Our real, reviewed policy.', $fresh->body);
        $this->assertSame('Privacyverklaring', $fresh->title);
    }

    public function test_re_seeding_does_not_duplicate_entries(): void
    {
        $this->seed(ContentSeeder::class);
        $count = ContentEntry::count();

        $this->seed(ContentSeeder::class);

        $this->assertSame($count, ContentEntry::count());
    }

    public function test_re_seeding_restores_a_deleted_entry(): void
    {
        $this->seed(ContentSeeder::class);
        ContentEntry::query()->ofType(ContentType::Legal)->where('slug', 'terms')->delete();

        $this->seed(ContentSeeder::class);

        $this->assertTrue(
            ContentEntry::query()->ofType(ContentType::Legal)->where('slug', 'terms')->exists(),
        );
    }

    /**
     * Shipping a placeholder unnoticed is the failure mode that matters, so
     * the text has to be unmistakably provisional.
     */
    public function test_the_legal_templates_flag_themselves_as_needing_review(): void
    {
        $this->seed(ContentSeeder::class);

        foreach (['privacy', 'terms'] as $slug) {
            $body = ContentEntry::query()->ofType(ContentType::Legal)->where('slug', $slug)->sole()->body;

            $this->assertStringContainsString('This is a template', $body);
            // Delimited with # because the class contains a forward slash.
            $this->assertMatchesRegularExpression('#\[[A-Z][A-Z /.]+\]#', $body, "The {$slug} page should carry obvious placeholders.");
        }
    }

    public function test_the_privacy_page_covers_the_mandatory_gdpr_disclosures(): void
    {
        $this->seed(ContentSeeder::class);

        $body = ContentEntry::query()->ofType(ContentType::Legal)->where('slug', 'privacy')->sole()->body;

        // Art. 13 GDPR requires each of these; missing ones are the most
        // commonly enforced transparency failures.
        foreach ([
            'controller',
            'Legal basis',
            'Retention',
            'Autoriteit Persoonsgegevens',
            'art. 77',
            'Portability',
            'art. 22',
            'Telecommunicatiewet',
        ] as $needle) {
            $this->assertStringContainsString($needle, $body, "The privacy policy should mention [{$needle}].");
        }
    }
}
