/**
 * Shared formatting for durations, intervals and response times so every
 * screen phrases the same value the same way.
 */

export function formatDuration(seconds: number): string {
    if (seconds < 60) {
        return `${Math.max(0, Math.round(seconds))}s`;
    }

    if (seconds < 3600) {
        return `${Math.floor(seconds / 60)}m`;
    }

    if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
    }

    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);

    return hours > 0 ? `${days}d ${hours}h` : `${days}d`;
}

/**
 * "Check every 30 seconds" style phrasing for an interval.
 */
export function formatInterval(seconds: number): string {
    if (seconds % 3600 === 0 && seconds >= 3600) {
        const hours = seconds / 3600;

        return hours === 1 ? 'Every hour' : `Every ${hours} hours`;
    }

    if (seconds % 60 === 0 && seconds >= 60) {
        const minutes = seconds / 60;

        return minutes === 1 ? 'Every minute' : `Every ${minutes} minutes`;
    }

    return `Every ${seconds} seconds`;
}

export function formatResponseMs(ms: number | null): string {
    if (ms === null) {
        return '-';
    }

    return ms >= 1000 ? `${(ms / 1000).toFixed(2)} s` : `${ms} ms`;
}

export function formatUptime(percentage: number | null): string {
    if (percentage === null) {
        return '-';
    }

    // Keep three decimals near the top of the range where they carry meaning.
    if (percentage >= 99.9 && percentage < 100) {
        return `${percentage.toFixed(3)}%`;
    }

    return `${percentage.toFixed(percentage === 100 ? 0 : 2)}%`;
}

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatRelative(value: string | null): string {
    if (!value) {
        return '-';
    }

    const seconds = Math.round((Date.now() - new Date(value).getTime()) / 1000);

    if (seconds < 10) {
        return 'just now';
    }

    return `${formatDuration(seconds)} ago`;
}
