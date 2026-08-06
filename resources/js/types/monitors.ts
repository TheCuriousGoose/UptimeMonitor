import type { User } from './auth';

type MonitorType = 'http' | 'keyword' | 'port' | 'ping' | 'dns' | 'ssl';

type MonitorStatus = 'up' | 'down' | 'degraded' | 'paused' | 'pending';

type MonitorConfig = {
    method?: string;
    /** Superseded by expected_status_codes, kept for monitors saved before it. */
    expected_status?: number | null;
    expected_status_codes?: string[];
    verify_ssl?: boolean;
    follow_redirects?: boolean;
    max_redirects?: number;
    /** Values may come back as the mask rather than the credential. */
    headers?: Record<string, string>;
    body?: string | null;
    content_type?: string | null;
    auth_type?: 'none' | 'basic' | 'bearer';
    auth_username?: string | null;
    auth_password?: string | null;
    auth_token?: string | null;
    keyword?: string;
    invert?: boolean;
    port?: number;
    record_type?: string;
    expected?: string | null;
    warn_days?: number;
};

interface Monitor {
    uuid: string;
    name: string;
    url: string;
    type: MonitorType;
    status: MonitorStatus;
    is_active: boolean;
    timeout: number;
    interval_seconds: number;
    confirmation_threshold: number;
    recovery_threshold: number;
    degraded_response_ms: number | null;
    is_degraded: boolean;
    config: MonitorConfig;
    last_checked_at: string | null;
    status_changed_at: string | null;
    created_by?: User;
    checks?: MonitorCheck[];
    notification_channels?: NotificationChannel[];
}

type MonitorCheck = {
    id: number;
    is_up: boolean;
    response_ms: number;
    error: string | null;
    meta: Record<string, unknown> | null;
    checked_at: string;
};

interface Incident {
    uuid: string;
    started_at: string;
    resolved_at: string | null;
    cause: string | null;
    failed_checks: number;
    duration_seconds: number;
    is_ongoing: boolean;
    monitor?: Monitor;
}

interface MonitorStats {
    uptime_percentage: number | null;
    total_checks: number;
    failed_checks: number;
    avg_response_ms: number | null;
    p95_response_ms: number | null;
    incidents: number;
    downtime_seconds: number;
}

interface SeriesPoint {
    bucket: string;
    /** Null when every check in the bucket failed — no successful sample. */
    avg_response_ms: number | null;
    failures: number;
    total: number;
}

interface DashboardSummary {
    total: number;
    up: number;
    degraded: number;
    down: number;
    paused: number;
    pending: number;
    ongoing_incidents: number;
    uptime_percentage: number | null;
    avg_response_ms: number | null;
}

type ChannelType =
    | 'email'
    | 'webhook'
    | 'slack'
    | 'discord'
    | 'pagerduty'
    | 'opsgenie'
    | 'teams';

type AlertScope = 'all' | 'selected';

interface AlertTemplate {
    title?: string | null;
    body?: string | null;
}

interface NotificationChannel {
    uuid: string;
    name: string;
    type: ChannelType;
    destination: string;
    is_active: boolean;
    alert_scope: AlertScope;
    templates?: Record<'down' | 'recovered', AlertTemplate> | null;
    /** Null switches reminders off; the limit caps how many are sent. */
    renotify_minutes: number | null;
    renotify_limit: number;
    /** HH:MM in `quiet_hours_timezone`, or null when the window is unset. */
    quiet_hours_start: string | null;
    quiet_hours_end: string | null;
    quiet_hours_timezone: string | null;
    monitors_count?: number;
    monitors?: string[];
}

type StatusPageMode = 'light' | 'dark' | 'system';

interface StatusPageLink {
    label: string;
    url: string;
}

/**
 * A status page's house style. Always sent complete by StatusPageResource —
 * never partially filled — so the editor can bind to it directly.
 */
interface StatusPageTheme {
    mode: StatusPageMode;
    font_family: string;
    font_url: string | null;
    radius: number;
    width: number;
    brand_color: string;
    background: string | null;
    foreground: string | null;
    up_color: string;
    down_color: string;
    warning_color: string;
    logo_url: string | null;
    favicon_url: string | null;
    footer_text: string | null;
    links: StatusPageLink[];
}

interface StatusPage {
    uuid: string;
    slug: string;
    title: string;
    description: string | null;
    is_published: boolean;
    theme: StatusPageTheme;
    public_url: string;
    monitors_count?: number;
    monitors?: Monitor[];
}

export type {
    AlertScope,
    AlertTemplate,
    ChannelType,
    DashboardSummary,
    Incident,
    Monitor,
    MonitorCheck,
    MonitorConfig,
    MonitorStats,
    MonitorStatus,
    MonitorType,
    NotificationChannel,
    SeriesPoint,
    StatusPage,
    StatusPageLink,
    StatusPageMode,
    StatusPageTheme,
};
