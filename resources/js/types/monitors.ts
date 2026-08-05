import type { User } from './auth';

type MonitorType = 'http' | 'keyword' | 'port' | 'ping' | 'dns' | 'ssl';

type MonitorStatus = 'up' | 'down' | 'paused' | 'pending';

type MonitorConfig = {
    method?: string;
    expected_status?: number | null;
    verify_ssl?: boolean;
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
    monitors_count?: number;
    monitors?: string[];
}

interface StatusPage {
    uuid: string;
    slug: string;
    title: string;
    description: string | null;
    is_published: boolean;
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
};
