import type { App, DirectiveBinding } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const apply = (el: HTMLElement, show: boolean): void => {
    el.style.display = show ? '' : 'none'
}

export function registerPermissionDirectives(app: App): void {
    const { can, canAny } = usePermissions()

    app.directive<HTMLElement, string>('can', {
        mounted(el, { value }: DirectiveBinding<string>) {
            apply(el, can(value))
        },
        updated(el, { value }: DirectiveBinding<string>) {
            apply(el, can(value))
        },
    })

    app.directive<HTMLElement, string | string[]>('can-any', {
        mounted(el, { value }: DirectiveBinding<string | string[]>) {
            apply(el, canAny(value))
        },
        updated(el, { value }: DirectiveBinding<string | string[]>) {
            apply(el, canAny(value))
        },
    })
}