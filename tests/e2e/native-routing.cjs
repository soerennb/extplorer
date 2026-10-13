const { chromium, expect } = require('@playwright/test');
const fs = require('node:fs/promises');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const crypto = require('node:crypto');
const { spawn, execFileSync } = require('node:child_process');

async function freePort() {
    const server = net.createServer();
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    await new Promise((resolve) => server.close(resolve));
    return port;
}

async function verifyArchive(archive) {
    const root = await fs.mkdtemp(path.join(os.tmpdir(), 'extplorer-native-'));
    const install = path.join(root, 'application');
    await fs.mkdir(install);
    execFileSync('php', ['-r', '$zip = new ZipArchive(); if ($zip->open($argv[1]) !== true || !$zip->extractTo($argv[2])) { exit(1); } $zip->close();', archive, install]);
    // The installed application must work without translation build sources.
    await fs.rm(path.join(install, 'resources'), { recursive: true, force: true });
    let browser;
    const results = [];
    try {
        browser = await chromium.launch();
        for (const [prefix, index] of [['/', ''], ['/extplorer/public/', ''], ['/', 'index.php'], ['/extplorer/public/', 'index.php']]) {
            const port = await freePort();
            const base = `http://127.0.0.1:${port}${prefix}`;
            const route = base + (index ? index + '/' : '');
            const writable = path.join(root, 'state-' + results.length);
            await fs.mkdir(writable);
            for (const directory of ['logs', 'cache', 'config', 'session', 'runtime']) await fs.mkdir(path.join(writable, directory));
            const env = { ...process.env, CI_ENVIRONMENT: 'production', EXTPLORER_WRITE_PATH: writable,
                EXTPLORER_BASE_URL: base, EXTPLORER_ADMIN_USER: 'native-admin', EXTPLORER_ADMIN_PASS: 'Native-Password!2026',
                EXTPLORER_ENCRYPTION_KEY: crypto.randomBytes(32).toString('hex'), 'app.indexPage': index,
                'app.forceGlobalSecureRequests': 'false', 'cookie.secure': 'false',
                NATIVE_PUBLIC: path.join(install, 'public'), NATIVE_PREFIX: prefix };
            execFileSync('php', ['spark', 'admin:bootstrap'], { cwd: install, env, stdio: 'pipe' });
            let output = '';
            const server = spawn('php', ['-d', 'error_log=/dev/stderr', '-S', `127.0.0.1:${port}`, path.resolve('tests/e2e/native-router.php')], { cwd: install, env });
            server.stderr.on('data', (value) => { output += value; });
            const context = await browser.newContext({ locale: 'en-US' });
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', (error) => errors.push(error.message));
            await page.addInitScript(() => {
                window.cspViolations = [];
                document.addEventListener('securitypolicyviolation', (event) => window.cspViolations.push(event.effectiveDirective));
            });
            try {
                for (let attempt = 0; attempt < 50; attempt++) {
                    try { if ((await fetch(route + 'health')).ok) break; } catch (error) {}
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }
                await page.goto(route + 'login');
                await page.locator('#login_username').fill('native-admin');
                await page.locator('#login_password').fill('Native-Password!2026');
                await page.getByTestId('login-submit').click();
                await expect(page.getByTestId('app-shell')).toBeVisible();
                // Large browser upload exercises the real binary PUT transport.
                await page.locator('[data-testid="file-item"][data-file-name="Home"]').dblclick();
                await expect(page.getByTestId('current-path')).toContainText('Home');
                await page.getByTestId('upload').click();
                await page.locator('#uploadFileInput').setInputFiles({ name: 'large.txt', mimeType: 'text/plain', buffer: Buffer.alloc(1048577, 'x') });
                const uploaded = page.waitForResponse((response) => response.url().includes('/api/upload') && (response.status() >= 400 || response.url().endsWith('/complete')));
                await page.getByTestId('upload-submit').click();
                const uploadResponse = await uploaded;
                if (uploadResponse.status() >= 400) throw new Error(await uploadResponse.text());
                await expect(page.locator('.swal2-success')).toBeVisible({ timeout: 20000 });
                await page.locator('.swal2-confirm').click();
                await page.getByTestId('upload-close').click();
                await expect(page.locator('[data-file-name="large.txt"]')).toBeVisible();
                await page.goto(route + 'admin#settings/mounts');
                const allowlist = page.locator('textarea[aria-label*="Remote endpoint"]');
                await allowlist.fill(' \n');
                const challenge = page.waitForResponse((response) => response.url() === route + 'api/settings' && response.status() === 428);
                await page.getByRole('button', { name: 'Save Settings' }).click();
                expect((await challenge).headers()['x-csrf-hash']).toBeTruthy();
                await page.locator('#step-up-password').fill('Native-Password!2026');
                const saved = page.waitForResponse((response) => response.url() === route + 'api/settings' && response.status() === 200);
                await page.locator('.swal2-confirm').click();
                expect((await saved).headers()['x-csrf-hash']).toBeTruthy();
                await expect(page.getByRole('button', { name: 'Save Settings' })).toBeDisabled();
                await allowlist.fill('  \n');
                const repeated = page.waitForResponse((response) => response.url() === route + 'api/settings' && response.request().method() === 'POST');
                await page.getByRole('button', { name: 'Save Settings' }).click();
                expect((await repeated).status()).toBe(200);
                expect(errors).toEqual([]);
                expect(await page.evaluate(() => window.cspViolations)).toEqual([]);
                results.push({ prefix, index, result: 'passed' });
                console.log(`Native routing passed: ${prefix} (${index || 'rewritten'})`);
            } catch (error) {
                await fs.mkdir('build', { recursive: true });
                await page.screenshot({ path: 'build/native-routing-failure.png' });
                console.error({ upload: await page.locator('#uploadModal').allTextContents(), errors, dialog: await page.locator('.swal2-container').allTextContents() });
                for (const file of await fs.readdir(path.join(writable, 'logs')).catch(() => [])) {
                    if (file.endsWith('.log')) console.error((await fs.readFile(path.join(writable, 'logs', file), 'utf8')).slice(-6000));
                }
                console.error(output.slice(-4000));
                throw error;
            } finally {
                await context.close();
                server.kill('SIGTERM');
                await new Promise((resolve) => server.once('exit', resolve));
            }
        }
        await fs.mkdir('build', { recursive: true });
        const checksum = crypto.createHash('sha256').update(await fs.readFile(archive)).digest('hex');
        await fs.writeFile('build/native-routing-results.json', JSON.stringify({ archive, sha256: checksum, results }, null, 2) + '\n');
    } finally {
        await browser?.close();
        await fs.rm(root, { recursive: true, force: true });
    }
}
if (!process.argv[2]) throw new Error('Usage: node tests/e2e/native-routing.cjs <final.zip>');
verifyArchive(path.resolve(process.argv[2])).catch((error) => { console.error(error); process.exitCode = 1; });
