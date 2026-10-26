// Isolated native-installation test with a real OIDC provider and example website.
const { chromium, expect } = require('@playwright/test');
const fs = require('node:fs/promises');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const https = require('node:https');
const http = require('node:http');
const crypto = require('node:crypto');
const { execFileSync, spawn } = require('node:child_process');

const image = 'quay.io/keycloak/keycloak:26.8.1@sha256:a78d6b79572168cb25886b80a8b5b8d4ed4390743e3eecb810538683e209dd6b';
if (process.argv.includes('--help')) { console.log('Usage: node tests/e2e/external-auth.cjs [extracted-archive-directory]'); process.exit(0); }
const install = path.resolve(process.argv[2] || '.');
const layouts = [['/', ''], ['/extplorer/public/', ''], ['/', 'index.php'], ['/extplorer/public/', 'index.php']];
const password = 'Fixture-Password!2026';

async function freePort() {
    const server = net.createServer();
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    await new Promise((resolve) => server.close(resolve));
    return port;
}
async function httpsReady(url, ca) {
    return new Promise((resolve) => {
        const request = https.get(url, { ca }, (response) => { response.resume(); resolve(response.statusCode === 200); });
        request.on('error', () => resolve(false));
        request.setTimeout(2000, () => { request.destroy(); resolve(false); });
    });
}
async function stop(process) {
    if (!process || process.exitCode !== null) return;
    const exited = new Promise((resolve) => process.once('exit', resolve));
    process.kill('SIGTERM');
    await exited;
}

// A separate website with its own cookie, password form and CSRF validation.
// It implements the bridge assertion documented in examples/auth/website-bridge.php.
function websiteFixture(tls, key, allowedCallbacks, eventKey) {
    const sessions = new Map();
    return https.createServer(tls, async (request, response) => {
        const url = new URL(request.url, 'https://localhost');
        const cookie = /(?:^|;\s*)website_session=([a-f0-9]+)/.exec(request.headers.cookie || '');
        let id = cookie && cookie[1];
        if (!id || !sessions.has(id)) {
            id = crypto.randomBytes(24).toString('hex');
            sessions.set(id, {});
            response.setHeader('Set-Cookie', `website_session=${id}; Secure; HttpOnly; SameSite=Lax; Path=/`);
        }
        const session = sessions.get(id);
        response.setHeader('Cache-Control', 'no-store');
        response.setHeader('Referrer-Policy', 'no-referrer');
        const form = () => {
            session.csrf = crypto.randomBytes(32).toString('hex');
            response.setHeader('Content-Type', 'text/html');
            response.end(`<form method="post" action="/session"><input type="hidden" name="csrf" value="${session.csrf}"><label for="website-password">Website password</label><input id="website-password" name="password" type="password" required><button>Continue</button></form>`);
        };
        const finish = () => {
            const params = session.transaction;
            const now = Math.floor(Date.now() / 1000);
            const claims = { iss: 'https://website.example', sub: 'website-user-123', name: 'Website Alice',
                groups: ['extplorer-users'], auth_time: session.authTime, iat: now, exp: now + 60,
                aud: params.callback, state: params.state, nonce: params.nonce };
            const payload = Buffer.from(JSON.stringify(claims)).toString('base64url');
            const ticket = payload + '.' + crypto.createHmac('sha256', key).update(payload).digest('hex');
            response.writeHead(303, { Location: params.callback + '?' + new URLSearchParams({ state: params.state, ticket }) });
            response.end();
        };
        if (request.method === 'GET' && url.pathname === '/bridge') {
            const params = Object.fromEntries(url.searchParams);
            if (!allowedCallbacks.has(params.callback) || !/^[a-f0-9]{64}$/.test(params.state || '') || !/^[a-f0-9]{64}$/.test(params.nonce || '')) {
                response.writeHead(400); response.end(); return;
            }
            session.transaction = params;
            if (!session.authTime || params.reauthenticate === '1') form(); else finish();
        } else if (request.method === 'POST' && url.pathname === '/session') {
            let body = '';
            for await (const chunk of request) body += chunk;
            const fields = new URLSearchParams(body);
            if (!session.csrf || fields.get('csrf') !== session.csrf || fields.get('password') !== password || !session.transaction) {
                response.writeHead(403); response.end(); return;
            }
            delete session.csrf;
            session.authTime = Math.floor(Date.now() / 1000);
            finish();
        } else if (request.method === 'POST' && url.pathname === '/logout') {
            let body = '';
            for await (const chunk of request) body += chunk;
            const fields = Object.fromEntries(new URLSearchParams(body));
            const expected = crypto.createHmac('sha256', key).update(`logout\n${fields.time}\n${fields.callback}\n${fields.state}`).digest('hex');
            if (!allowedCallbacks.has(fields.callback.replace('login/external-logout/callback', 'login/external/website/callback'))
                || Math.abs(Date.now() / 1000 - Number(fields.time)) > 60 || fields.signature !== expected) {
                response.writeHead(400); response.end(); return;
            }
            delete session.authTime;
            const target = fields.callback.replace('login/external-logout/callback', 'auth/events/website');
            const event = { iss: 'https://website.example', aud: target, provider: 'website', id: crypto.randomUUID(),
                iat: Math.floor(Date.now() / 1000), type: 'session.revoked', sub: 'website-user-123' };
            const data = JSON.stringify(event);
            const status = await new Promise((resolve, reject) => {
                const delivery = https.request(target, { ca: tls.cert, method: 'POST', headers: { 'Content-Type': 'application/json',
                    'X-Extplorer-Event-Time': String(event.iat), 'X-Extplorer-Event-Signature': 'v1=' + crypto.createHmac('sha256', eventKey).update(event.iat + '.' + data).digest('hex') } }, (result) => {
                    result.resume(); result.on('end', () => resolve(result.statusCode));
                });
                delivery.on('error', reject); delivery.end(data);
            });
            if (status !== 200) { response.writeHead(502); response.end(); return; }
            response.writeHead(303, { Location: fields.callback + '?' + new URLSearchParams({ state: fields.state }) });
            response.end();
        } else { response.writeHead(404); response.end(); }
    });
}

async function signInProvider(page, provider) {
    if (provider === 'company') {
        await page.locator('#password').waitFor();
        if (await page.locator('input#username').isVisible()) await page.locator('input#username').fill('alice');
        await page.locator('#password').fill(password);
        await page.locator('#kc-login').click();
    } else {
        await page.locator('#website-password').fill(password);
        await page.getByRole('button', { name: 'Continue', exact: true }).click();
    }
}

async function run() {
    const root = await fs.mkdtemp(path.join(os.tmpdir(), 'extplorer-auth-e2e-'));
    const container = 'extplorer-auth-' + crypto.randomBytes(6).toString('hex');
    let browser, website, php, proxy;
    let containerStarted = false;
    let phpLog = '';
    try {
        const appPort = await freePort();
        const phpPort = await freePort();
        const idpPort = await freePort();
        const websitePort = await freePort();
        const idp = `https://127.0.0.1:${idpPort}`;
        const issuer = idp + '/realms/extplorer';
        const fixture = path.join(root, 'keycloak');
        await fs.mkdir(fixture, { mode: 0o755 });
        const certificate = path.join(fixture, 'certificate.pem');
        const privateKey = path.join(fixture, 'private-key.pem');
        execFileSync('openssl', ['req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1', '-subj', '/CN=localhost',
            '-addext', 'subjectAltName=IP:127.0.0.1,DNS:localhost', '-keyout', privateKey, '-out', certificate], { stdio: 'pipe' });
        await fs.chmod(privateKey, 0o644); // Ephemeral test-only key, readable by the non-root fixture container.
        const ca = await fs.readFile(certificate);
        const signingKey = crypto.randomBytes(32).toString('hex');
        const eventKey = crypto.randomBytes(32).toString('hex');
        await fs.writeFile(path.join(root, 'event-key'), eventKey);
        await fs.writeFile(path.join(root, 'website-key'), signingKey);
        await fs.writeFile(path.join(root, 'oidc-secret'), 'fixture-client-secret');
        await fs.writeFile(path.join(fixture, 'realm.json'), JSON.stringify({
            realm: 'extplorer', enabled: true, registrationAllowed: false,
            clients: layouts.map(([prefix, index], layoutIndex) => ({ clientId: 'extplorer-' + layoutIndex, secret: 'fixture-client-secret', publicClient: false, standardFlowEnabled: true,
                redirectUris: [`https://127.0.0.1:${appPort}/*`], attributes: { 'pkce.code.challenge.method': 'S256',
                    'post.logout.redirect.uris': '+', 'backchannel.logout.session.required': 'true',
                    'backchannel.logout.url': `http://127.0.0.1:${phpPort}${prefix}${index ? index + '/' : ''}auth/oidc/company/backchannel-logout` },
                protocolMappers: [{ name: 'groups', protocol: 'openid-connect', protocolMapper: 'oidc-group-membership-mapper',
                    config: { 'claim.name': 'groups', 'full.path': 'false', 'id.token.claim': 'true', 'access.token.claim': 'false' } }] })),
            groups: [{ name: 'extplorer-users' }],
            users: [{ username: 'alice', enabled: true, email: 'alice@example.test', emailVerified: true, firstName: 'Alice', lastName: 'Fixture',
                groups: ['extplorer-users'], credentials: [{ type: 'password', value: password, temporary: false }] }],
        }));
        execFileSync('docker', ['run', '-d', '--memory', '2g', '--cpus', '2', '--name', container, '--network', 'host', '-e', `KC_HTTPS_PORT=${idpPort}`,
            '-v', `${fixture}:/fixtures:ro`, '-v', `${path.join(fixture, 'realm.json')}:/opt/keycloak/data/import/realm.json:ro`,
            '-e', `KC_HOSTNAME=${idp}`, '-e', 'KC_HTTPS_CERTIFICATE_FILE=/fixtures/certificate.pem',
            '-e', 'JAVA_OPTS_KC_HEAP=-Xms64m -Xmx512m',
            '-e', 'KC_HTTPS_CERTIFICATE_KEY_FILE=/fixtures/private-key.pem', image, 'start-dev', '--import-realm'], { stdio: 'pipe' });
        containerStarted = true;
        let ready = false;
        for (let attempt = 0; attempt < 240; attempt++) {
            if (await httpsReady(issuer + '/.well-known/openid-configuration', ca)) { ready = true; break; }
            await new Promise((resolve) => setTimeout(resolve, 500));
        }
        if (!ready) throw new Error('Keycloak fixture did not become ready');
        const allowedCallbacks = new Set(layouts.map(([prefix, index]) => `https://127.0.0.1:${appPort}${prefix}${index ? index + '/' : ''}login/external/website/callback`));
        website = websiteFixture({ cert: ca, key: await fs.readFile(privateKey) }, signingKey, allowedCallbacks, eventKey);
        await new Promise((resolve) => website.listen(websitePort, '127.0.0.1', resolve));
        proxy = https.createServer({ cert: ca, key: await fs.readFile(privateKey) }, (request, response) => {
            const upstream = http.request({ hostname: '127.0.0.1', port: phpPort, path: request.url, method: request.method, headers: request.headers }, (result) => {
                response.writeHead(result.statusCode, result.headers); result.pipe(response);
            });
            upstream.on('error', (error) => { if (error.code !== 'ECONNREFUSED') console.error('TLS fixture upstream:', request.method, error.code, error.message); if (!response.headersSent) { response.writeHead(502); response.end(); } else response.destroy(); });
            request.pipe(upstream);
        });
        await new Promise((resolve) => proxy.listen(appPort, '127.0.0.1', resolve));
        browser = await chromium.launch();
        for (const [layoutIndex, [prefix, index]] of layouts.entries()) {
            const base = `https://127.0.0.1:${appPort}${prefix}`;
            const route = base + (index ? index + '/' : '');
            const writable = path.join(root, 'state-' + layoutIndex);
            for (const dir of ['logs', 'cache', 'config', 'session', 'runtime']) await fs.mkdir(path.join(writable, dir), { recursive: true });
            const auth = { default_provider: 'company', providers: {
                company: { type: 'oidc', label: 'Company account', issuer, client_id: 'extplorer-' + layoutIndex, rp_logout_enabled: true, backchannel_logout_enabled: true, webdav_enabled: true, client_secret_file: path.join(root, 'oidc-secret'),
                    base_role: 'admin', group_map: { 'extplorer-users': [] }, allow_private_targets: true },
                website: { type: 'custom', class: 'WebsiteProvider', label: 'My website', issuer: 'https://website.example',
                    login_url: `https://127.0.0.1:${websitePort}/bridge`, logout_url: `https://127.0.0.1:${websitePort}/logout`, rp_logout_enabled: true, webdav_enabled: true, signing_key_file: path.join(root, 'website-key'),
                    events_secret_file: path.join(root, 'event-key'), supports_reauthentication: true, base_role: 'admin', group_map: { 'extplorer-users': [] } },
            }, extension_path: path.join(install, 'examples/auth') };
            const configurationFile = path.join(root, `auth-${layoutIndex}.json`);
            await fs.writeFile(configurationFile, JSON.stringify(auth));
            const env = { ...process.env, CI_ENVIRONMENT: 'production', EXTPLORER_WRITE_PATH: writable, EXTPLORER_BASE_URL: base,
                EXTPLORER_AUTH_CONFIG_FILE: configurationFile, EXTPLORER_ADMIN_USER: 'local-admin', EXTPLORER_ADMIN_PASS: password,
                EXTPLORER_ENCRYPTION_KEY: crypto.randomBytes(32).toString('hex'), 'app.indexPage': index,
                'app.forceGlobalSecureRequests': 'false', 'cookie.secure': 'true', NATIVE_TLS: '1',
                NATIVE_PUBLIC: path.join(install, 'public'), NATIVE_PREFIX: prefix };
            execFileSync('php', ['-d', 'variables_order=EGPCS', 'spark', 'admin:bootstrap'], { cwd: install, env, stdio: 'pipe' });
            phpLog = '';
            php = spawn('php', ['-d', 'variables_order=EGPCS', '-d', `openssl.cafile=${certificate}`, '-d', `curl.cainfo=${certificate}`, '-S', `127.0.0.1:${phpPort}`, path.resolve('tests/e2e/native-router.php')], { cwd: install, env });
            php.stderr.on('data', (data) => { phpLog += data; });
            for (let attempt = 0; attempt < 60; attempt++) {
                if (await httpsReady(route + 'login?local=1', ca)) break;
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            const identities = [];
            for (const provider of ['company', 'website']) {
                const context = await browser.newContext({ ignoreHTTPSErrors: true, locale: 'en-US' });
                console.log(`Fresh owned browser context: ${context.pages().length} existing pages`);
                const page = await context.newPage();
                const errors = [];
                let siblingContext;
                page.on('pageerror', (error) => errors.push(error.message));
                await page.addInitScript(() => {
                    window.cspViolations = [];
                    document.addEventListener('securitypolicyviolation', (event) => window.cspViolations.push(event.effectiveDirective));
                });
                try {
                    await page.goto(route + 'login?local=1');
                    if (layoutIndex === 0 && provider === 'company') {
                        await fs.mkdir('build/external-auth', { recursive: true });
                        await page.screenshot({ path: 'build/external-auth/login-desktop.png' });
                        await page.setViewportSize({ width: 390, height: 844 });
                        await page.screenshot({ path: 'build/external-auth/login-mobile.png' });
                        await page.setViewportSize({ width: 1280, height: 720 });
                    }
                    if (provider === 'company') await page.goto(route + 'login');
                    else await page.locator(`a[href*="login/external/${provider}"]`).click();
                    await signInProvider(page, provider);
                    await expect(page.getByTestId('app-shell')).toBeVisible();
                    await page.waitForFunction(() => typeof Api !== 'undefined' && !document.getElementById('app').hasAttribute('v-cloak'));
                    const details = await page.evaluate(() => Api.get('profile/details'));
                    expect(details.auth_source).toBe('external');
                    expect(details.username).toMatch(/^ext_[a-f0-9]{32}$/);
                    identities.push(details.username);
                    const challenge = page.waitForResponse((response) => response.url() === route + 'api/settings' && response.status() === 428);
                    const mutation = page.evaluate(() => Api.post('settings', { session_idle_timeout_minutes: 30 }));
                    mutation.catch(() => {});
                    expect((await challenge).headers()['x-csrf-hash']).toBeTruthy();
                    await expect(page.locator('.swal2-confirm')).toBeVisible();
                    const popupPromise = context.waitForEvent('page');
                    await page.locator('.swal2-confirm').click();
                    const popup = await popupPromise;
                    await signInProvider(popup, provider);
                    await mutation;
                    await expect.poll(() => popup.isClosed()).toBe(true);
                    const dav = await page.evaluate(() => Api.post('profile/webdav-credentials', { label: 'E2E client' }));
                    expect(dav.credential.expires_at).toBeGreaterThan(Date.now() / 1000);
                    if (layoutIndex === 0) {
                        await page.locator('a.dropdown-item').filter({ hasText: 'Profile & Settings' }).evaluate((link) => link.click());
                        await page.locator('#profile-tab-security').click();
                        await expect(page.locator('#profile-panel-security code').filter({ hasText: details.username })).toBeVisible();
                        await page.screenshot({ path: `build/external-auth/profile-${provider}-desktop.png` });
                        await page.setViewportSize({ width: 390, height: 844 });
                        await page.screenshot({ path: `build/external-auth/profile-${provider}-mobile.png` });
                        await page.setViewportSize({ width: 1280, height: 720 });
                        await page.locator('.modal.show .btn-close').click();
                    }
                    const davRequest = (method, suffix = '', data) => new Promise((resolve, reject) => {
                        const req = https.request(route + 'dav/' + suffix, { ca, method, headers: { Authorization: 'Basic ' + Buffer.from(details.username + ':' + dav.secret).toString('base64'), Depth: '0' } }, (res) => { let body = ''; res.on('data', (chunk) => { body += chunk; }); res.on('end', () => { if (res.statusCode === 404) console.error('DAV 404:', body.slice(0, 1200)); resolve(res.statusCode); }); });
                        req.on('error', reject); req.end(data);
                    });
                    expect(await davRequest('PROPFIND')).toBe(207);
                    expect(await davRequest('PUT', 'lifecycle.txt', 'external DAV fixture')).toBe(201);
                    expect(await davRequest('GET', 'lifecycle.txt')).toBe(200);
                    expect(await davRequest('DELETE', 'lifecycle.txt')).toBe(204);
                    const blocked = await page.evaluate(async () => {
                        const response = await fetch(Api.route('api/profile/password'), { method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfHash }, body: JSON.stringify({ old_password: 'anything', password: 'New-Password-123!' }) });
                        return { status: response.status, csrf: response.headers.get('X-CSRF-HASH') };
                    });
                    expect(blocked.status).toBe(403);
                    expect(blocked.csrf).toBeTruthy();
                    // The following GET refreshes the browser's CSRF token after the deliberate rejected PUT.
                    await page.evaluate(() => Api.get('profile/details'));
                    await page.evaluate(() => fetch(Api.route('logout'), { method: 'POST', headers: { 'X-CSRF-TOKEN': window.csrfHash } }));
                    await page.goto(route + 'login?logged_out=1');
                    await expect(page.locator('#login_username')).toBeVisible();
                    expect(await davRequest('PROPFIND')).toBe(207);
                    // Local logout preserved the upstream login; explicit SSO returns immediately.
                    await page.goto(route + 'login/external/' + provider);
                    await expect(page.getByTestId('app-shell')).toBeVisible();
                    await page.waitForFunction(() => typeof Api !== 'undefined' && !document.getElementById('app').hasAttribute('v-cloak'));
                    siblingContext = await browser.newContext({ ignoreHTTPSErrors: true });
                    console.log(`Fresh owned second browser context: ${siblingContext.pages().length} existing pages`);
                    await siblingContext.addCookies((await context.cookies()).filter((cookie) => provider === 'company' ? cookie.path.startsWith('/realms/') : cookie.name === 'website_session'));
                    const siblingPage = await siblingContext.newPage();
                    await siblingPage.goto(route + 'login/external/' + provider);
                    await expect(siblingPage.getByTestId('app-shell')).toBeVisible();
                    // Use the actual UI and its confirmation to initiate provider logout.
                    await page.locator('[data-testid="provider-logout"]').evaluate((link) => link.click());
                    await page.locator('.swal2-confirm').click();
                    await expect(page.locator('#login_username')).toBeVisible();
                    expect(await davRequest('PROPFIND')).toBe(207);
                    expect((await siblingPage.request.get(route + 'api/profile/details')).status()).toBe(401);
                    await siblingContext.close(); siblingContext = null;
                    await page.goto(route + 'login/external/' + provider);
                    await signInProvider(page, provider); // provider logout removed its own login too
                    await expect(page.getByTestId('app-shell')).toBeVisible();
                    if (provider === 'website') {
                        const event = { iss: 'https://website.example', aud: route + 'auth/events/website', provider: 'website',
                            id: crypto.randomUUID(), iat: Math.floor(Date.now() / 1000), type: 'account.updated', revision: 1,
                            sub: 'website-user-123', status: 'suspended', groups: [] };
                        const body = JSON.stringify(event);
                        const result = await context.request.post(event.aud, { data: body, headers: { 'Content-Type': 'application/json',
                            'X-Extplorer-Event-Time': String(event.iat), 'X-Extplorer-Event-Signature': 'v1=' + crypto.createHmac('sha256', eventKey).update(event.iat + '.' + body).digest('hex') } });
                        expect(result.status()).toBe(200);
                        expect(result.headers()['x-csrf-hash']).toBeTruthy();
                        expect(await davRequest('PROPFIND')).toBe(401);
                        const denied = await page.request.get(route + 'api/profile/details');
                        expect(denied.status()).toBe(401);
                    }
                    expect(errors).toEqual([]);
                    expect(await page.evaluate(() => window.cspViolations)).toEqual([]);
                    console.log(`External auth passed: ${provider}, ${prefix}, ${index || 'rewritten'}`);
                } catch (error) {
                    await fs.mkdir('build/external-auth', { recursive: true });
                    await page.screenshot({ path: `build/external-auth/failure-${layoutIndex}-${provider}.png` }).catch(() => {});
                    console.error('Page errors:', errors);
                    console.error('Failed page path:', new URL(page.url()).pathname);
                    const events = await fs.readFile(path.join(writable, 'logs/activity_logs.php'), 'utf8').catch(() => '');
                    if (events) console.error('Authentication events:', JSON.parse(events.slice(events.indexOf('\n') + 1)).slice(0, 8));
                    throw error;
                } finally { if (siblingContext) await siblingContext.close(); await context.close(); }
            }
            expect(new Set(identities).size).toBe(2);
            await stop(php); php = null;
        }
    } catch (error) {
        // Redact query strings, which may contain short-lived assertions.
        if (phpLog) console.error(phpLog.replace(/\?[^\s\]]+/g, '?[redacted]').split('\n').filter((line) => /error|warning|fatal|exception/i.test(line)).slice(-15).join('\n'));
        if (containerStarted) console.error(execFileSync('docker', ['logs', '--tail', '25', container], { encoding: 'utf8' }));
        throw error;
    } finally {
        if (browser) await browser.close();
        await stop(php);
        if (proxy) await new Promise((resolve) => proxy.close(resolve));
        if (website) await new Promise((resolve) => website.close(resolve));
        if (containerStarted) execFileSync('docker', ['rm', '-f', container], { stdio: 'pipe' });
        await fs.rm(root, { recursive: true, force: true });
    }
}

run().catch((error) => { console.error(error); process.exitCode = 1; });
