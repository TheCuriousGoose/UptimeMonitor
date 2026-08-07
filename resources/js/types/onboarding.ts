/**
 * First-run setup state, derived server side from what the account has rather
 * than from a stored "finished onboarding" flag.
 */
interface OnboardingProgress {
    has_monitor: boolean;
    has_channel: boolean;
    has_status_page: boolean;
    dismissed: boolean;
}

export type { OnboardingProgress };
