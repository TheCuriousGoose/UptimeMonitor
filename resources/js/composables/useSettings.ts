import { router } from '@inertiajs/vue3'
import { useSettingsStore } from '@/stores/settingsStore'

router.on('navigate', (event) => {
    const { settings } = event.detail.page.props as { settings?: Record<string, any> }

    if (settings) {
        const store = useSettingsStore()
        store.initializeFromProps(settings)
    }
})

export function useSettings() {
    const store = useSettingsStore()

    return store
}
