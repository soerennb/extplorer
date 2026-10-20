// Cross-version checks use the published ZIP/image, never a checkout of old code.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const crypto = require('node:crypto');
const { execFileSync, spawn } = require('node:child_process');
const { chromium, expect } = require('@playwright/test');
const baseline = require('../release/baseline-3.1.1.json');
const fixture = path.resolve('tests/release/upgrade-state.php');
const router = path.resolve('tests/e2e/native-router.php');
const results = [];
function run(program, args, options = {}) {
    try { return execFileSync(program, args, { encoding: 'utf8', timeout: 240000, maxBuffer: 64 * 1024 * 1024, ...options }); }
    catch (error) { console.error(error.stdout?.toString().slice(-4000), error.stderr?.toString().slice(-4000)); throw error; }
}
async function freePort() {
    const server = net.createServer();
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    await new Promise((resolve) => server.close(resolve));
    return port;
}
async function login(browser, base) {
    const context = await browser.newContext({ locale: 'en-US' });
    try {
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.addInitScript(() => {
            window.upgradeCspErrors = [];
            document.addEventListener('securitypolicyviolation', (event) => window.upgradeCspErrors.push(event.effectiveDirective));
        });
        await page.goto(base + '/login');
        await page.locator('#login_username').fill('upgrade-admin');
        await page.locator('#login_password').fill('Upgrade-Password!2026');
        await page.getByTestId('login-submit').click();
        await expect(page.getByTestId('app-shell')).toBeVisible();
        await page.waitForLoadState('networkidle');
        expect(await page.evaluate(() => window.forcePasswordChange === true)).toBe(false);
        expect(errors).toEqual([]);
        expect(await page.evaluate(() => window.upgradeCspErrors)).toEqual([]);
    } finally { await context.close(); }
}
function record(deployment, output) {
    const result = JSON.parse(output.trim().split('\n').at(-1));
    results.push({ deployment, ...result });
    console.log(`${deployment}: ${result.application_version} ${result.phase}, ${result.checks} checks passed`);
}
async function nativeUpgrade(root, oldZip, newZip, browser) {
    const oldApp = path.join(root, 'old');
    const newApp = path.join(root, 'new');
    const writable = path.join(root, 'writable');
    const backup = path.join(root, 'backup');
    const external = path.join(root, 'external');
    fs.mkdirSync(external);
    for (const app of [oldApp, newApp]) fs.mkdirSync(app);
    for (const [zip, app] of [[oldZip, oldApp], [newZip, newApp]]) {
        run('php', ['-r', '$z=new ZipArchive(); if($z->open($argv[1])!==true || !$z->extractTo($argv[2])) exit(1);', zip, app]);
        fs.rmSync(path.join(app, 'resources'), { recursive: true, force: true });
    }
    for (const directory of ['config', 'logs', 'session', 'cache', 'runtime']) fs.mkdirSync(path.join(writable, directory), { recursive: true });
    const env = { ...process.env, CI_ENVIRONMENT: 'production', EXTPLORER_WRITE_PATH: writable,
        EXTPLORER_ENCRYPTION_KEY: crypto.randomBytes(32).toString('hex'), EXTPLORER_ADMIN_USER: 'bootstrap-admin', EXTPLORER_ADMIN_PASS: 'Bootstrap-Password!2026',
        'app.forceGlobalSecureRequests': 'false', 'cookie.secure': 'false', EXTPLORER_STATE_DRIVER: 'file', UPGRADE_MOUNT_ROOT: external, EXTPLORER_MOUNT_ROOT_ALLOWLIST: external };
    const check = (app, phase) => record('native', run('php', [fixture, app, phase], { env }));
    async function browse(app) {
        const port = await freePort();
        const base = `http://127.0.0.1:${port}`;
        const server = spawn('php', ['-S', `127.0.0.1:${port}`, router], { cwd: app, env: { ...env, EXTPLORER_BASE_URL: base + '/', NATIVE_PUBLIC: path.join(app, 'public'), NATIVE_PREFIX: '/' }, stdio: ['ignore', 'ignore', 'pipe'] });
        let log = '';
        server.stderr.on('data', (chunk) => { log += chunk; });
        try {
            let ready = false;
            for (let attempt = 0; attempt < 100; attempt++) {
                try { if ((await fetch(base + '/health')).ok) { ready = true; break; } } catch {}
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            if (!ready) throw new Error('Upgrade test server did not become ready');
            await login(browser, base);
        } catch (error) { console.error(log.slice(-4000)); throw error; }
        finally { server.kill(); await new Promise((resolve) => server.once('exit', resolve)); }
    }
    run('php', ['spark', 'admin:bootstrap'], { cwd: oldApp, env });
    check(oldApp, 'seed');
    await browse(oldApp);
    fs.cpSync(writable, backup, { recursive: true });
    run('php', ['spark', 'security:migrate'], { cwd: newApp, env });
    check(newApp, 'verify');
    await browse(newApp);
    check(newApp, 'exercise');
    check(newApp, 'completed');
    await browse(newApp); // Fresh PHP process and fresh browser session after activation.
    check(oldApp, 'completed'); // Verify code rollback can still read post-upgrade data.
    await browse(oldApp);
    fs.rmSync(writable, { recursive: true });
    fs.cpSync(backup, writable, { recursive: true });
    check(oldApp, 'verify');
    check(oldApp, 'exercise');
    await browse(oldApp);
}
async function dockerUpgrade(root, candidate, browser) {
    const port = await freePort();
    const base = `http://127.0.0.1:${port}`;
    const override = path.join(root, 'docker.json');
    const secret = path.join(root, 'admin-password');
    const external = path.join(root, 'docker-external');
    fs.mkdirSync(external, { mode: 0o777 });
    fs.chmodSync(external, 0o777);
    fs.writeFileSync(secret, 'e2e-admin-password\n', { mode: 0o600 });
    const env = { ...process.env, COMPOSE_PROJECT_NAME: `release-upgrade-${process.pid}`, EXTPLORER_HTTP_PORT: String(port), EXTPLORER_BASE_URL: base + '/', E2E_BASE_URL: base, EXTPLORER_ADMIN_PASSWORD_HOST_FILE: secret };
    const flags = ['compose', '-f', 'docker-compose.yml', '-f', 'docker-compose.secrets.yml.example', '-f', 'tests/e2e/docker-compose.yml', '-f', override];
    const compose = (args, options = {}) => run('docker', [...flags, ...args], { env, ...options });
    const selectImage = (image) => fs.writeFileSync(override, JSON.stringify({ services: {
        'extplorer-init': { image, pull_policy: 'never' },
        'extplorer-app': { image, pull_policy: 'never', volumes: [`${fixture}:/opt/release-upgrade-state.php:ro`, `${external}:/opt/upgrade-mount`], environment: { EXTPLORER_ENCRYPTION_KEY: 'release-upgrade-fixture-key-2026-10-19', UPGRADE_MOUNT_ROOT: '/opt/upgrade-mount', EXTPLORER_MOUNT_ROOT_ALLOWLIST: '/opt/upgrade-mount' } },
    } }));
    const check = (phase) => record('docker', compose(['exec', '-T', '--user', '82:82', 'extplorer-app', 'php', '/opt/release-upgrade-state.php', '/var/www/html/current', phase]));
    const start = () => compose(['up', '--detach', '--wait', '--wait-timeout', '180']);
    const oldImage = `ghcr.io/soerennb/extplorer3@${baseline.image_digest}`;
    run('docker', ['pull', oldImage]);
    selectImage(oldImage);
    try {
        start();
        const initialRelease = compose(['exec', '-T', 'extplorer-app', 'readlink', '/var/www/html/current']).trim().replace('.releases/', '');
        check('seed');
        await login(browser, base);
        const backup = compose(['exec', '-T', '--user', '0:0', 'extplorer-app', 'tar', '-czf', '-', '-C', '/var/www/html/writable', '.'], { encoding: null });
        compose(['stop', 'extplorer-app', 'extplorer-web']);
        selectImage(candidate);
        start();
        check('verify');
        await login(browser, base);
        check('exercise');
        compose(['restart', 'extplorer-app', 'extplorer-web']);
        start();
        check('completed');
        await login(browser, base);
        compose(['run', '--rm', 'extplorer-init', '--rollback', initialRelease]);
        compose(['stop', 'extplorer-app', 'extplorer-web']);
        selectImage(oldImage);
        start();
        check('completed');
        await login(browser, base);
        compose(['down', '--volumes', '--remove-orphans']);
        compose(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'tar', 'extplorer-init', '-xzf', '-', '-C', '/var/www/html/writable'], { input: backup });
        start();
        check('verify');
        check('exercise');
        await login(browser, base);
    } catch (error) { console.error(compose(['logs', '--no-color']).slice(-6000)); throw error; }
    finally { compose(['down', '--volumes', '--remove-orphans']); }
}
async function main() {
    const [oldArgument, newArgument, candidate] = process.argv.slice(2);
    if (!oldArgument || !newArgument) throw new Error('Usage: node tests/e2e/release-upgrade.cjs <published-3.1.1.zip> <candidate.zip> [candidate-image]');
    const oldZip = path.resolve(oldArgument);
    const newZip = path.resolve(newArgument);
    if (crypto.createHash('sha256').update(fs.readFileSync(oldZip)).digest('hex') !== baseline.zip_sha256) throw new Error('Published baseline ZIP checksum mismatch');
    const root = fs.mkdtempSync(path.join(os.tmpdir(), 'extplorer-upgrade-'));
    const browser = await chromium.launch();
    try {
        await nativeUpgrade(root, oldZip, newZip, browser);
        if (candidate) await dockerUpgrade(root, candidate, browser);
        fs.mkdirSync('build', { recursive: true });
        fs.writeFileSync('build/release-upgrade-results.json', JSON.stringify({ baseline, candidate_zip_sha256: crypto.createHash('sha256').update(fs.readFileSync(newZip)).digest('hex'), candidate_image: candidate || null, results }, null, 2) + '\n');
    } finally { await browser.close(); fs.rmSync(root, { recursive: true, force: true }); }
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
