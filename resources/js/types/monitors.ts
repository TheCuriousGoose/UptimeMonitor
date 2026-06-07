import type { User } from "./auth";

type MonitorType = 'http';

interface Monitor {
    uuid: string;
    name: string;
    url: string;
    type: MonitorType;
    checks?: MonitorCheck[];
    is_up: boolean;
    created_by: User;
    timeout: string;
    check_interval: string;
}

type MonitorCheck = {
    id: number;
    response_ms: number;
    checked_at: string;
    meta: { status_code: number; checker: string } | null;
} & (
    | { is_up: true; error?: never }
    | { is_up: false; error: string }
);

export type { Monitor, MonitorCheck, MonitorType }