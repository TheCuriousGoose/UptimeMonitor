/**
 * Cron for people who do not write cron. Windows still store a plain
 * expression, so these helpers build one from a handful of pickers and read it
 * back again — falling back to the raw text whenever an expression says
 * something the pickers cannot express.
 */
import { trans } from '@/lib/i18n';

export type ScheduleMode = 'daily' | 'weekly' | 'monthly' | 'custom';

export interface CronSchedule {
    mode: ScheduleMode;
    /** HH:MM, read in the window's own timezone. */
    time: string;
    /** 0 (Sunday) through 6, used by the weekly mode. */
    weekdays: number[];
    dayOfMonth: number;
    /** The raw expression, authoritative only in custom mode. */
    expression: string;
}

export const DEFAULT_CRON = '0 2 * * 0';

const DAY_TOKENS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

function isNumeric(field: string): boolean {
    return /^\d+$/.test(field);
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

/** Accepts "3", "WED" or "Wednesday"; 7 is Sunday, as cron allows. */
function toWeekday(token: string): number | null {
    const value = token.trim().toLowerCase();

    if (isNumeric(value)) {
        const day = Number(value);

        return day >= 0 && day <= 7 ? day % 7 : null;
    }

    const index = DAY_TOKENS.indexOf(value.slice(0, 3));

    return index === -1 ? null : index;
}

/** Expands "1-5" and "0,6" into the days they cover, or null if it cannot. */
function parseWeekdays(field: string): number[] | null {
    const days = new Set<number>();

    for (const part of field.split(',')) {
        const bounds = part.split('-').map(toWeekday);

        if (bounds.length > 2 || bounds.some((day) => day === null)) {
            return null;
        }

        const [from, to = from] = bounds as number[];

        if (to < from) {
            return null;
        }

        for (let day = from; day <= to; day++) {
            days.add(day);
        }
    }

    return days.size > 0 ? [...days].sort((a, b) => a - b) : null;
}

export function parseCron(expression: string | null): CronSchedule {
    const raw = (expression ?? '').trim();

    const custom: CronSchedule = {
        mode: 'custom',
        time: '02:00',
        weekdays: [0],
        dayOfMonth: 1,
        expression: raw || DEFAULT_CRON,
    };

    const fields = raw.split(/\s+/);

    if (fields.length !== 5) {
        return custom;
    }

    const [minute, hour, dayOfMonth, month, weekday] = fields;

    // Anything other than a fixed minute and hour on every month — steps,
    // ranges, several times a day — belongs to the raw expression.
    if (!isNumeric(minute) || !isNumeric(hour) || month !== '*') {
        return custom;
    }

    if (Number(minute) > 59 || Number(hour) > 23) {
        return custom;
    }

    const base = {
        ...custom,
        time: `${pad(Number(hour))}:${pad(Number(minute))}`,
    };

    if (dayOfMonth === '*' && weekday === '*') {
        return { ...base, mode: 'daily' };
    }

    if (dayOfMonth === '*') {
        const weekdays = parseWeekdays(weekday);

        return weekdays ? { ...base, mode: 'weekly', weekdays } : custom;
    }

    if (weekday === '*' && isNumeric(dayOfMonth)) {
        const day = Number(dayOfMonth);

        return day >= 1 && day <= 31
            ? { ...base, mode: 'monthly', dayOfMonth: day }
            : custom;
    }

    return custom;
}

export function buildCron(schedule: CronSchedule): string {
    if (schedule.mode === 'custom') {
        return schedule.expression.trim();
    }

    // Defaulted rather than trusted: a cleared time input reads back as "".
    const [hour = 0, minute = 0] = schedule.time
        .split(':')
        .map((part) => Number(part) || 0);
    const at = `${minute} ${hour}`;

    if (schedule.mode === 'daily') {
        return `${at} * * *`;
    }

    if (schedule.mode === 'monthly') {
        return `${at} ${schedule.dayOfMonth} * *`;
    }

    const days = [...new Set(schedule.weekdays)].sort((a, b) => a - b);

    return `${at} * * ${(days.length > 0 ? days : [0]).join(',')}`;
}

/** A rough shape check so the custom field can complain before the server does. */
export function isCronShaped(expression: string): boolean {
    return expression.trim().split(/\s+/).length === 5;
}

export function weekdayLabel(
    day: number,
    style: 'short' | 'long' | 'narrow' = 'long',
): string {
    // 2024-01-07 was a Sunday, so day 0 lands on it.
    return new Intl.DateTimeFormat(undefined, {
        weekday: style,
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(2024, 0, 7 + day)));
}

function joinWeekdays(days: number[]): string {
    const names = [...days]
        .sort((a, b) => a - b)
        .map((day) => weekdayLabel(day, 'short'));

    if (typeof Intl.ListFormat !== 'function') {
        return names.join(', ');
    }

    return new Intl.ListFormat(undefined, { type: 'conjunction' }).format(
        names,
    );
}

export function describeSchedule(schedule: CronSchedule): string {
    switch (schedule.mode) {
        case 'daily':
            return trans('maintenance.schedule.summary.daily', {
                time: schedule.time,
            });

        case 'weekly':
            // Every day ticked is a daily schedule however it was reached.
            return schedule.weekdays.length === 7
                ? trans('maintenance.schedule.summary.daily', {
                      time: schedule.time,
                  })
                : trans('maintenance.schedule.summary.weekly', {
                      days: joinWeekdays(schedule.weekdays),
                      time: schedule.time,
                  });

        case 'monthly':
            return trans('maintenance.schedule.summary.monthly', {
                day: schedule.dayOfMonth,
                time: schedule.time,
            });

        default:
            return schedule.expression;
    }
}

export function describeCron(expression: string | null): string {
    return describeSchedule(parseCron(expression));
}
