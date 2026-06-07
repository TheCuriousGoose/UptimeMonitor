import { defineStore } from 'pinia';
import { ref } from 'vue';

interface Setting {
    key: string;
    value: any;
    [key: string]: any;
}

export const useSettingsStore = defineStore('settings', () => {
    const settings = ref<Record<string, Setting[]>>({});

    const initializeFromProps = (settingsData: Record<string, Setting[]>) => {
        settings.value = settingsData;
    };

    const get = (key: string, defaultValue: any = null): any => {
        // Search all sections for the key
        for (const section of Object.values(settings.value)) {
            const setting = section.find((s) => s.key === key);

            if (setting) {
return setting.value;
}
        }

        return defaultValue;
    };

    return {
        settings,
        initializeFromProps,
        get,
    };
});
