<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * notification_channels.config holds webhook URLs, Slack and Discord
     * tokens, and PagerDuty and Opsgenie keys — every one of them enough to
     * post as the user. They were stored as readable JSON, so a database dump
     * or a stray backup handed them over intact.
     *
     * monitors.config is encrypted in the same pass even though it holds
     * nothing sensitive yet: it is about to gain request headers and auth
     * credentials, and doing it now costs one migration instead of two.
     *
     * The column type change is not cosmetic. The encrypter emits a base64
     * envelope, which MySQL's json type rejects as invalid JSON.
     */
    private const COLUMNS = [
        'monitors' => true,           // nullable
        'notification_channels' => false,
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $nullable) {
            Schema::table($table, function (Blueprint $blueprint) use ($nullable) {
                $blueprint->text('config')->nullable($nullable)->change();
            });
        }

        foreach (array_keys(self::COLUMNS) as $table) {
            $this->rewrite($table, function (string $value) {
                // Skip anything already encrypted. A migration that dies
                // mid-chunk gets re-run by hand, and encrypting twice is not
                // recoverable: the cast decrypts one layer, fails to parse the
                // envelope underneath, and the credential is gone.
                try {
                    Crypt::decryptString($value);

                    return $value;
                } catch (DecryptException) {
                    return Crypt::encryptString($value);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::COLUMNS) as $table) {
            $this->rewrite($table, function (string $value) {
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return $value;
                }
            });
        }

        foreach (self::COLUMNS as $table => $nullable) {
            Schema::table($table, function (Blueprint $blueprint) use ($nullable) {
                $blueprint->json('config')->nullable($nullable)->change();
            });
        }
    }

    /**
     * Chunked by primary key rather than loaded whole: these tables are small
     * today, but a migration that holds every row in memory is a bad habit to
     * leave behind in one that will be re-run on every install.
     */
    private function rewrite(string $table, callable $transform): void
    {
        DB::table($table)
            ->select('id', 'config')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $transform) {
                foreach ($rows as $row) {
                    if (! is_string($row->config) || $row->config === '') {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['config' => $transform($row->config)]);
                }
            });
    }
};
