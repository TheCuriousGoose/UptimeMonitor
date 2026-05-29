import type { User } from "./auth";

type MonitorType = 'http';

interface Monitor {
    uuid: string;
    name: string;
    url: string;
    type: MonitorType;
    monitor_check: MonitorCheck;
    is_up: boolean;
    created_by: User;
    timeout: string;
    check_interval: string;
}

type MonitorCheck = {
    status_code: number;
    response_ms: number;
} & (
    | { is_up: true; error?: never }
    | { is_up: false; error: string }
);

export type { Monitor, MonitorCheck, MonitorType }