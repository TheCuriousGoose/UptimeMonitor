<?php

namespace App\Enums;

/**
 * Scopes an API token can be granted. Checked by Sanctum's ability
 * middleware on each route, on top of — never instead of — the acting
 * user's own role permissions: a token can only ever narrow what its
 * owner could already do, not extend it.
 *
 * Deliberately carries no label: like MonitorType, the backend ships raw
 * values and the frontend owns display text via api_tokens.abilities.* —
 * one place responsible for translation, not two.
 */
enum ApiAbility: string
{
    case MonitorsRead = 'monitors:read';
    case MonitorsWrite = 'monitors:write';
    case IncidentsRead = 'incidents:read';
    case ChecksTrigger = 'checks:trigger';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $ability) => $ability->value, self::cases());
    }
}
