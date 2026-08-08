import { mount } from '@vue/test-utils';
import type { VueWrapper } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import MonitorForm from '@/components/monitors/MonitorForm.vue';
import { i18n, trans } from '@/lib/i18n';
import type {
    Monitor,
    MonitorType,
    MonitorTypeOptions,
} from '@/types/monitors';

/**
 * The server's own answer, copied from MonitorType::formOptions(). The shape
 * is asserted against the real controller in InertiaPropShapeTest, so if the
 * two drift, that test fails rather than these passing on a stale copy.
 */
export const typeOptions: MonitorTypeOptions = {
    url_types: ['http', 'keyword', 'ssl'],
    methods: ['GET', 'POST', 'HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    content_types: [
        'application/json',
        'application/x-www-form-urlencoded',
        'text/plain',
        'application/xml',
    ],
    auth_types: ['none', 'basic', 'bearer'],
    record_types: ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'],
};

export const allTypes: MonitorType[] = [
    'http',
    'keyword',
    'port',
    'ping',
    'dns',
    'ssl',
];

/**
 * Stands in for Inertia's Form, which would otherwise want a router. It
 * renders a real <form> because that is the point: the component's save and
 * its "Test check" button both build their payload with `new FormData(form)`,
 * so the DOM is the contract under test.
 */
const FormStub = defineComponent({
    name: 'InertiaFormStub',
    inheritAttrs: false,
    setup(_props, { slots, attrs }) {
        return () =>
            h(
                'form',
                { id: attrs.id as string },
                slots.default?.({ errors: {}, processing: false }),
            );
    },
});

export function mountForm(defaults?: Partial<Monitor>): VueWrapper {
    return mount(MonitorForm, {
        props: {
            types: allTypes,
            typeOptions,
            channels: [],
            ...(defaults ? { defaults } : {}),
        },
        global: {
            plugins: [i18n],
            stubs: { Form: FormStub, Link: true, teleport: true },
        },
    });
}

/**
 * Everything the form would actually post, read back off the rendered DOM.
 *
 * Multi-valued keys are kept as arrays so an indexed list cannot silently
 * collapse to its last entry.
 */
export function payload(wrapper: VueWrapper): Record<string, string[]> {
    const form = wrapper.find('form').element as HTMLFormElement;
    const entries: Record<string, string[]> = {};

    for (const [key, value] of new FormData(form).entries()) {
        (entries[key] ??= []).push(String(value));
    }

    return entries;
}

/** The `config[...]` keys present in a payload, without the wrapper. */
export function configKeys(entries: Record<string, string[]>): string[] {
    return Object.keys(entries)
        .filter((key) => key.startsWith('config['))
        .map((key) => key.slice('config['.length, -1))
        .sort();
}

export async function selectType(
    wrapper: VueWrapper,
    type: MonitorType,
): Promise<void> {
    const button = wrapper
        .findAll('button[type="button"]')
        .find((candidate) => candidate.text().includes(labelFor(type)));

    if (!button) {
        throw new Error(`No type card rendered for "${type}".`);
    }

    await button.trigger('click');
}

function labelFor(type: MonitorType): string {
    // The app's own helper, not i18n.global.t: the latter's generic overloads
    // blow the compiler's instantiation depth on a template-literal key.
    return trans(`monitors.form.type.options.${type}`);
}
