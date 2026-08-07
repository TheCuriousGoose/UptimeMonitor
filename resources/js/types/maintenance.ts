type MaintenanceRecurrence = 'once' | 'recurring';

/**
 * The four keys that describe when a window opens. `cron` and
 * `duration_minutes` belong to a recurring window, the two dates to a one-off
 * one; the server blanks whichever pair the chosen recurrence does not use.
 */
interface MaintenanceSchedule {
    recurrence: MaintenanceRecurrence;
    starts_at: string;
    ends_at: string;
    cron: string;
    duration_minutes: number;
}

interface MaintenanceWindow {
    uuid: string;
    name: string;
    recurrence: MaintenanceRecurrence;
    timezone: string;
    starts_at: string | null;
    ends_at: string | null;
    cron: string | null;
    duration_minutes: number | null;
    is_active: boolean;
    is_active_now: boolean;
    next_occurrence_at: string | null;
    monitors?: string[];
}

export type { MaintenanceRecurrence, MaintenanceSchedule, MaintenanceWindow };
