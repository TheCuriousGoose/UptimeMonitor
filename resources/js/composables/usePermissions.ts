import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';

export interface UsePermissionsReturn {
    permissions: ComputedRef<string[]>;
    can: (permission: string) => boolean;
    canAny: (permissions: string | string[]) => boolean;
}

export function usePermissions(): UsePermissionsReturn {
    const page = usePage();

    const permissions = computed<string[]>(
        () =>
            (page.props.auth as { permissions?: string[] } | undefined)
                ?.permissions ?? [],
    );

    const can = (permission: string): boolean =>
        permissions.value.includes(permission);

    const canAny = (value: string | string[]): boolean =>
        ([] as string[]).concat(value).some(can);

    return { permissions, can, canAny };
}
