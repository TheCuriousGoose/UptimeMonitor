import { describe, expect, it } from 'vitest';
import {
    configKeys,
    mountForm,
    payload,
    selectType,
} from './monitorFormPayload';

/**
 * The monitor form posts nothing directly from the controls that edit it.
 * Switches, selects and chip lists are not form inputs, so each one is
 * mirrored into a hidden input, and both saving and the "Test check" button
 * read the result with `new FormData(form)`.
 *
 * That makes a dropped field silent: no error, no validation failure, just a
 * monitor saved with the default. These assert the payload the DOM actually
 * produces for each type.
 */
describe('the monitor form payload', () => {
    it('always posts the scheduling fields', () => {
        const entries = payload(mountForm());

        for (const key of [
            'type',
            'interval_seconds',
            'timeout',
            'confirmation_threshold',
            'recovery_threshold',
            'is_active',
        ]) {
            expect(entries, `missing "${key}"`).toHaveProperty(key);
        }
    });

    it('posts the http config for the default type', () => {
        const entries = payload(mountForm());

        expect(entries.type).toEqual(['http']);
        expect(configKeys(entries)).toEqual([
            'auth_type',
            'body',
            'follow_redirects',
            'method',
            'verify_ssl',
        ]);
    });

    it('adds the keyword fields, keeping the http ones', async () => {
        const wrapper = mountForm();
        await selectType(wrapper, 'keyword');

        const keys = configKeys(payload(wrapper));

        expect(keys).toContain('keyword');
        expect(keys).toContain('invert');
        // A keyword check is an http check that also reads the body, so it
        // must keep carrying the request options.
        expect(keys).toContain('method');
        expect(keys).toContain('verify_ssl');
    });

    it('posts only the port field for a port check', async () => {
        const wrapper = mountForm();
        await selectType(wrapper, 'port');

        expect(configKeys(payload(wrapper))).toEqual(['port']);
    });

    it('posts the dns fields for a dns check', async () => {
        const wrapper = mountForm();
        await selectType(wrapper, 'dns');

        expect(configKeys(payload(wrapper))).toEqual([
            'expected',
            'record_type',
        ]);
    });

    it('posts the warning window for an ssl check', async () => {
        const wrapper = mountForm();
        await selectType(wrapper, 'ssl');

        expect(configKeys(payload(wrapper))).toEqual(['warn_days']);
    });

    it('posts no config at all for a ping check', async () => {
        const wrapper = mountForm();
        await selectType(wrapper, 'ping');

        expect(configKeys(payload(wrapper))).toEqual([]);
    });

    it('drops the config keys belonging to the type that was switched away from', async () => {
        const wrapper = mountForm();

        await selectType(wrapper, 'dns');
        expect(configKeys(payload(wrapper))).toContain('record_type');

        await selectType(wrapper, 'port');

        const keys = configKeys(payload(wrapper));

        expect(keys).not.toContain('record_type');
        expect(keys).not.toContain('expected');
        expect(keys).toEqual(['port']);
    });

    it('round-trips a saved monitor rather than reverting it to defaults', () => {
        const entries = payload(
            mountForm({
                type: 'dns',
                url: 'example.com',
                name: 'DNS',
                interval_seconds: 600,
                timeout: 12,
                config: { record_type: 'MX', expected: 'mail.example.com' },
            }),
        );

        expect(entries['config[record_type]']).toEqual(['MX']);
        expect(entries['config[expected]']).toEqual(['mail.example.com']);
        expect(entries.interval_seconds).toEqual(['600']);
        expect(entries.timeout).toEqual(['12']);
    });

    /**
     * The list is submitted even when empty, so an update can clear it.
     * Without the blank entry the key is absent, which the server cannot tell
     * apart from "leave the channels alone".
     */
    it('always submits the notification channel key', () => {
        const entries = payload(mountForm());

        expect(entries['notification_channels[]']).toEqual(['']);
    });
});
