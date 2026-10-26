const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup(blocked = false, cancelWaiting = false) {
    const popup = { closed: false, location: { replace(url) { this.url = url; } }, close() { this.closed = true; } };
    const window = { externalAuthentication: true, location: { href: 'https://files.test/app/', origin: 'https://files.test' },
        open: () => blocked ? null : popup, addEventListener() {} };
    const Swal = { fire: (options) => options.preConfirm ? Promise.resolve({ isConfirmed: options.preConfirm() === true })
        : cancelWaiting ? Promise.resolve({ isDismissed: true }) : new Promise(() => {}), showValidationMessage() {}, close() {} };
    window.Swal = Swal;
    const context = vm.createContext({ window, Swal, URL, console, setTimeout: (resolve) => resolve() });
    vm.runInContext(fs.readFileSync('public/assets/js/api.js', 'utf8'), context);
    return { api: vm.runInContext('Api', context), popup };
}

test('external step-up exchanges the memory-only proof, retains the page and closes its popup', async () => {
    const { api, popup } = setup();
    const sent = [];
    api.request = async (route, method, body) => {
        sent.push({ route, method, body });
        if (route === 'security/step-up') return { method: 'external', start_url: 'https://files.test/app/login/external/company?transaction=state', challenge: 'state', exchange_secret: 'memory-only' };
        if (route === 'security/step-up/exchange') {
            assert.equal(body.exchange_secret, 'memory-only');
            return { token: 'grant-token' };
        }
        return {};
    };
    assert.equal(await api.requestStepUp('mount.create'), 'grant-token');
    assert.equal(popup.closed, true);
    assert.ok(!popup.location.url.includes('memory-only'));
    assert.equal(api.stepUpDialogOpen, false);
    assert.deepEqual(sent.map((item) => item.route), ['security/step-up', 'security/step-up/exchange', 'security/step-up/cancel']);
});

test('blocked popups do not create authentication transactions', async () => {
    const { api } = setup(true);
    api.request = async () => { assert.fail('No transaction may be created without a popup'); };
    assert.equal(await api.requestStepUp('mount.create'), null);
});

test('external step-up closes the popup and cancels its transaction after failure', async () => {
    const { api, popup } = setup();
    const cancelled = [];
    api.request = async (route, method, body) => {
        if (route === 'security/step-up') return { method: 'external', start_url: 'https://attacker.test/', challenge: 'state', exchange_secret: 'proof' };
        if (route.endsWith('/cancel')) { cancelled.push(body.challenge); return {}; }
        assert.fail('Do not exchange or navigate to a foreign origin');
    };
    await assert.rejects(api.requestStepUp('mount.create'), /external_auth_failed/);
    assert.equal(popup.closed, true);
    assert.deepEqual(cancelled, ['state']);
});

test('cancelling confirmation discards even a grant arriving with the in-flight exchange', async () => {
    const { api, popup } = setup(false, true);
    const cancelled = [];
    api.request = async (route, method, body) => {
        if (route === 'security/step-up') return { method: 'external', start_url: 'https://files.test/login/external/company', challenge: 'state', exchange_secret: 'proof' };
        if (route.endsWith('/exchange')) return { token: 'must-be-discarded' };
        if (route.endsWith('/cancel')) { cancelled.push(body.challenge); return {}; }
    };
    assert.equal(await api.requestStepUp('mount.create'), null);
    assert.equal(popup.closed, true);
    assert.deepEqual(cancelled, ['state']);
});
