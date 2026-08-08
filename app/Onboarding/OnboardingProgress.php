<?php

namespace App\Onboarding;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\StatusPage;
use App\Models\User;

/**
 * How far through first-run setup an account is.
 *
 * Derived from what the account actually has rather than tracked as its own
 * flag, so it cannot drift: deleting your only integration puts that step back
 * whatever a stored "completed onboarding" boolean would have claimed.
 *
 * Deliberately not memoised. A static cache here was keyed by nothing, so the
 * first account to ask froze the answer for every later one in the process.
 * Callers that need it twice pass the array to shouldRedirect() instead.
 */
final class OnboardingProgress
{
    /** Where the "I have seen enough" choice lives on the user. */
    public const PREFERENCE_KEY = 'onboarding_dismissed';

    /**
     * @return array<string, bool>
     */
    public static function for(User $user): array
    {
        return [
            'has_monitor' => Monitor::query()->forUser($user)->exists(),
            'has_channel' => NotificationChannel::query()
                ->where('user_id', $user->id)
                ->exists(),
            'has_status_page' => StatusPage::query()
                ->where('user_id', $user->id)
                ->exists(),
            'dismissed' => (bool) data_get($user->preferences, self::PREFERENCE_KEY, false),
        ];
    }

    /**
     * @param  array<string, bool>  $progress
     */
    public static function shouldRedirect(User $user, array $progress): bool
    {
        return ! $progress['has_monitor']
            && ! $progress['dismissed']
            && $user->can('create', Monitor::class);
    }
}
