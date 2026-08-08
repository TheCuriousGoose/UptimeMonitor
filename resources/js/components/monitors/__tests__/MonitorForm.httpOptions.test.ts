import { describe, expect, it } from 'vitest';
import type { MonitorConfig } from '@/types/monitors';
import { mountForm, payload } from './monitorFormPayload';

function httpPayload(config: MonitorConfig): Record<string, string[]> {
    return payload(
        mountForm({
            type: 'http',
            url: 'https://example.com',
            name: 'API',
            config,
        }),
    );
}

/**
 * The request options are the part of the payload with real conditions
 * attached — credentials only travel for the scheme that uses them, and a
 * content type is meaningless without a body. Each of those is a branch that
 * can silently stop emitting.
 */
describe('the http request options payload', () => {
    it('posts neither credential when auth is off', () => {
        const entries = httpPayload({ auth_type: 'none' });

        expect(entries).toHaveProperty('config[auth_type]', ['none']);
        expect(entries).not.toHaveProperty('config[auth_username]');
        expect(entries).not.toHaveProperty('config[auth_password]');
        expect(entries).not.toHaveProperty('config[auth_token]');
    });

    it('posts the username and password for basic auth, but never the token', () => {
        const entries = httpPayload({
            auth_type: 'basic',
            auth_username: 'ops',
            auth_password: 'hunter2',
            auth_token: 'left-over-from-bearer',
        });

        expect(entries['config[auth_username]']).toEqual(['ops']);
        expect(entries['config[auth_password]']).toEqual(['hunter2']);
        expect(entries).not.toHaveProperty('config[auth_token]');
    });

    it('posts the token for bearer auth, but never the username or password', () => {
        const entries = httpPayload({
            auth_type: 'bearer',
            auth_token: 'abc123',
            auth_username: 'left-over-from-basic',
            auth_password: 'left-over-from-basic',
        });

        expect(entries['config[auth_token]']).toEqual(['abc123']);
        expect(entries).not.toHaveProperty('config[auth_username]');
        expect(entries).not.toHaveProperty('config[auth_password]');
    });

    it('omits the content type when there is no body to describe', () => {
        const entries = httpPayload({ body: '   ' });

        expect(entries).not.toHaveProperty('config[content_type]');
    });

    it('posts the content type once a body is present', () => {
        const entries = httpPayload({
            body: '{"ping":true}',
            content_type: 'application/json',
        });

        expect(entries['config[body]']).toEqual(['{"ping":true}']);
        expect(entries['config[content_type]']).toEqual(['application/json']);
    });

    it('posts each expected status code under its own index', () => {
        const entries = httpPayload({
            expected_status_codes: ['200', '204', '2xx'],
        });

        expect(entries['config[expected_status_codes][0]']).toEqual(['200']);
        expect(entries['config[expected_status_codes][1]']).toEqual(['204']);
        expect(entries['config[expected_status_codes][2]']).toEqual(['2xx']);
    });

    it('posts each header under its own name', () => {
        const entries = httpPayload({
            headers: { 'X-Api-Key': 'secret', Accept: 'application/json' },
        });

        expect(entries['config[headers][X-Api-Key]']).toEqual(['secret']);
        expect(entries['config[headers][Accept]']).toEqual([
            'application/json',
        ]);
    });

    it('posts booleans as 1 and 0 rather than true and false', () => {
        const entries = httpPayload({
            verify_ssl: false,
            follow_redirects: false,
        });

        // The config cast stores whatever it is handed, so "false" would come
        // back as a truthy string — see ConfigCast::Bool.
        expect(entries['config[verify_ssl]']).toEqual(['0']);
        expect(entries['config[follow_redirects]']).toEqual(['0']);

        const on = httpPayload({ verify_ssl: true, follow_redirects: true });

        expect(on['config[verify_ssl]']).toEqual(['1']);
        expect(on['config[follow_redirects]']).toEqual(['1']);
    });
});
