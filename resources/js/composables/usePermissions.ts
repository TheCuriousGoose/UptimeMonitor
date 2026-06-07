import { router } from '@inertiajs/vue3'
import { reactive } from 'vue'

interface PermissionState {
    permissions: string[]
}

export interface UsePermissionsReturn {
    can: (permission: string) => boolean
    canAny: (permissions: string | string[]) => boolean
}

const state = reactive<PermissionState>({
    permissions: [],
})

router.on('navigate', (event) => {
    const { auth } = event.detail.page.props as { auth?: { permissions?: string[] } }
    state.permissions = auth?.permissions ?? []
})

export function usePermissions(): UsePermissionsReturn {
    const can = (permission: string): boolean =>
        state.permissions.includes(permission)

    const canAny = (permissions: string | string[]): boolean =>
        ([] as string[]).concat(permissions).some(can)

    return { can, canAny }
}