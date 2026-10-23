const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function transport(fetch) {
    const window = { baseUrl: 'https://files.test/extplorer/', routeBaseUrl: 'https://files.test/extplorer/index.php/',
        csrfHash: 'initial', addEventListener() {}, location: { pathname: '/', search: '' } };
    const context = vm.createContext({ window, fetch, URL, DOMException, console });
    vm.runInContext(fs.readFileSync('public/assets/js/api.js', 'utf8'), context);
    return { api: vm.runInContext('Api', context), window };
}
function response(token, payload = {}, status = 200) {
    return { ok: status < 400, status, headers: { get: (name) => name.toLowerCase() === 'content-type' ? 'application/json' : token },
        json: async () => payload };
}

test('concurrent mutations refresh their CSRF token before sending the next request', async () => {
    const requests = [];
    const { api, window } = transport((url, options) => new Promise((resolve) => requests.push({ url, options, resolve })));
    const first = api.post('mkdir', { path: 'Home/a' });
    const second = api.post('mkdir', { path: 'Home/b' });
    await new Promise(setImmediate);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url, 'https://files.test/extplorer/index.php/api/mkdir');
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'initial');
    requests[0].resolve(response('next')); await first;
    await new Promise(setImmediate);
    assert.equal(requests[1].options.headers['X-CSRF-TOKEN'], 'next');
    requests[1].resolve(response('last')); await second;
    assert.equal(window.csrfHash, 'last');
});

test('step-up releases the mutation queue and retries with the refreshed token', async () => {
    const sent = [];
    const { api } = transport(async (url, options) => {
        sent.push({ url, options });
        if (sent.length === 1) return response('challenge', { error: 'step_up_required', action: 'settings.update' }, 428);
        if (sent.length === 2) return response('confirmed', { token: 'grant' });
        return response('saved', { status: 'success' });
    });
    api.requestStepUp = async (action) => (await api.post('security/step-up', { action })).token;
    assert.equal((await api.post('settings', {})).status, 'success');
    assert.equal(sent[1].options.headers['X-CSRF-TOKEN'], 'challenge');
    assert.equal(sent[2].options.headers['X-CSRF-TOKEN'], 'confirmed');
    assert.equal(sent[2].options.headers['X-Extplorer-Step-Up'], 'grant');
});

test('the step-up grant is kept in memory for later sensitive mutations and renewed when rejected', async () => {
    const sent = [];
    let prompts = 0;
    const { api } = transport(async (url, options) => {
        sent.push({ url, options });
        const grant = options.headers['X-Extplorer-Step-Up'];
        if (url.endsWith('/security/step-up')) return response('t', { token: prompts === 1 ? 'first' : 'second' });
        if (grant === 'first' && sent.filter((entry) => entry.options.headers['X-Extplorer-Step-Up'] === 'first').length > 2) {
            return response('t', { error: 'step_up_required', action: 'settings.update' }, 428);
        }
        if (!grant) return response('t', { error: 'step_up_required', action: 'settings.update' }, 428);
        return response('t', { status: 'success' });
    });
    api.requestStepUp = async (action) => { prompts++; return (await api.post('security/step-up', { action })).token; };

    assert.equal((await api.post('settings', {})).status, 'success');
    assert.equal((await api.post('settings', {})).status, 'success');
    assert.equal(prompts, 1, 'a valid in-memory grant is reused without prompting');
    assert.equal(sent.at(-1).options.headers['X-Extplorer-Step-Up'], 'first');

    assert.equal((await api.post('settings', {})).status, 'success');
    assert.equal(prompts, 2, 'a rejected grant is discarded and renewed');
    assert.equal(sent.at(-1).options.headers['X-Extplorer-Step-Up'], 'second');
});

test('a late GET response cannot overwrite a regenerated token', async () => {
    let finishRead;
    const { api, window } = transport(async (url, options) => options.method
        ? response('fresh') : new Promise((resolve) => { finishRead = resolve; }));
    const read = api.get('ls'); await new Promise(setImmediate);
    await api.post('mkdir');
    finishRead(response('stale')); await read;
    assert.equal(window.csrfHash, 'fresh');
});

test('session expiry rejects promptly so loading and upload cleanup can finish', async () => {
    const { api } = transport(async () => response('refresh', { code: 'session_expired' }, 401));
    await assert.rejects(api.post('mkdir'), { name: 'SessionExpiredError' });
});

test('chunk uploads use binary PUT and abort the session after a failed chunk', async () => {
    const calls = [];
    const api = { post: async (path) => { calls.push(path); return { id: 'session' }; },
        put: async (path, body, options) => { calls.push(path); assert.equal(options.contentType, 'application/octet-stream'); throw new Error('connection lost'); },
        delete: async (path) => { calls.push(path); } };
    const context = vm.createContext({ Api: api });
    vm.runInContext(fs.readFileSync('public/assets/js/file-upload-client.js', 'utf8'), context);
    const client = vm.runInContext('FileUploadClient', context);
    await assert.rejects(client.upload({ name: 'big.txt', size: 2097152, slice() { return {}; } }, { path: 'Home' }), /connection lost/);
    assert.deepEqual(calls, ['upload/session', 'upload/session/session/chunk/0', 'upload/session/session']);
});
