const { expect } = require('@playwright/test');

module.exports = async function verifyMailBoundary(page) {
    const request = page.context().request;
    const settings = await request.get('/api/settings');
    let token = settings.headers()['x-csrf-hash'];
    expect(token).toBeTruthy();
    const post = async (path, options) => {
        const response = await request.post('/api/' + path, { ...options, headers: { 'X-CSRF-TOKEN': token } });
        token = response.headers()['x-csrf-hash'];
        expect(token).toBeTruthy();
        return response;
    };

    const name = 'mail-program-' + Date.now().toString(36);
    const marker = name + '-executed.txt';
    const program = '/var/www/html/writable/file_manager_root/' + name;
    const script = '#!/bin/sh\nprintf executed > /var/www/html/writable/file_manager_root/' + marker + '\n';
    expect((await post('upload', { multipart: { path: 'Home', file: {
        name, mimeType: 'application/octet-stream', buffer: Buffer.from(script)
    } } })).status()).toBe(200);
    expect((await post('chmod', { data: { path: 'Home/' + name, mode: '700' } })).status()).toBe(200);

    const rejected = await post('settings', { data: { email_protocol: 'sendmail', sendmail_path: program } });
    expect(rejected.status()).toBe(422);
    expect((await rejected.json()).error).toBe('sendmail_path_read_only');
    for (const route of ['settings/test-email', 'settings/validate-email']) {
        const response = await post(route, { data: { email: 'test@example.test', email_protocol: 'sendmail', sendmail_path: program } });
        expect(response.status()).toBe(422);
        expect((await response.json()).error).toBe('email_saved_settings_only');
    }
    const listed = await request.get('/api/ls?path=Home');
    expect((await listed.json()).items.some((file) => file.name === marker)).toBe(false);
    expect((await post('rm', { data: { path: 'Home/' + name } })).status()).toBe(200);

    await page.goto('/admin#settings/email');
    await page.getByRole('combobox', { name: 'Protocol', exact: true }).selectOption('sendmail');
    await expect(page.getByText('The server operator configures this program. It cannot be changed here.')).toBeVisible();
    await expect(page.locator('input[aria-label="Sendmail Path"]')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Validate', exact: true })).toBeDisabled();
};
