const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function load(file, name, overrides = {}) {
    const context = vm.createContext({
        Vue: { reactive: (value) => value },
        window: { innerWidth: 1200, addEventListener() {} },
        localStorage: { getItem() { return null; }, setItem() {}, removeItem() {} },
        i18n: { t: (key) => key }, console, ...overrides
    });
    vm.runInContext(fs.readFileSync(`public/assets/js/${file}`, 'utf8'), context);
    return vm.runInContext(name, context);
}

test('latest navigation owns files and loading even when an older response arrives last', async () => {
    const pending = [];
    const store = load('store.js', 'store', { Api: { get: () => new Promise((resolve) => pending.push(resolve)) } });
    const old = store.loadPath('Home/old');
    const current = store.loadPath('Home/current');
    pending[0]({ items: [{ name: 'old' }], total: 1 });
    await old;
    assert.equal(store.isLoading, true);
    pending[1]({ items: [{ name: 'current' }], total: 1 });
    await current;
    assert.equal(store.cwd, 'Home/current');
    assert.equal(store.files[0].name, 'current');
    assert.equal(store.isLoading, false);
});

test('navigation after search discards the stale search result', async () => {
    const pending = [];
    const store = load('store.js', 'store', { Api: { get: () => new Promise((resolve) => pending.push(resolve)) } });
    const search = store.performSearch('old');
    const navigation = store.loadPath('Home');
    pending[1]({ items: [{ name: 'current' }], total: 1 }); await navigation;
    pending[0]({ items: [{ name: 'old-search' }], total: 1 }); await search;
    assert.equal(store.files[0].name, 'current');
});

for (const name of ['AdminUsers']) {
    test(`${name} omits an unchanged password and sends a deliberate replacement`, async () => {
        const sent = [];
        const component = load(`components/${name}.js`, name, { Api: { put: async (_, payload) => sent.push(payload) } });
        const context = { ...component.methods, editingUser: { username: 'alice', password: '', role: 'user' },
            isNew: false, loadUsers: async () => {}, loadEffectivePermissions() {}, toastSuccess() {}, toastError() {} };
        await context.saveUser();
        assert.equal(Object.hasOwn(sent[0], 'password'), false);
        context.editingUser = { username: 'alice', password: 'replacement-password', role: 'user' };
        await context.saveUser();
        assert.equal(sent[1].password, 'replacement-password');
    });
}


test('the compiled quick administration overview renders without the removed mode', async () => {
    const Vue = require('vue');
    const { renderToString } = require('@vue/server-renderer');
    const component = load('compiled/components/UserAdmin.js', 'UserAdmin', {
        Vue, Api: { route: (path) => 'https://example.test/' + path }
    });
    const app = Vue.createSSRApp(component);
    app.config.warnHandler = () => {};
    const html = await renderToString(app);
    assert.match(html, /Recent activity/);
    assert.match(html, /https:\/\/example.test\/admin#users/);
});


test('a reload during navigation refreshes the requested destination', async () => {
    const pending = [];
    const paths = [];
    const store = load('store.js', 'store', { Api: { get: (_, params) => {
        paths.push(params.path);
        return new Promise((resolve) => pending.push(resolve));
    } } });
    store.cwd = 'Home/previous';
    const navigation = store.loadPath('Home/current');
    assert.equal(store.cwd, 'Home/current');
    const reload = store.reload();
    assert.deepEqual(paths, ['Home/current', 'Home/current']);
    pending[0]({ items: [{ name: 'stale' }], total: 1 }); await navigation;
    assert.equal(store.isLoading, true);
    pending[1]({ items: [{ name: 'fresh' }], total: 1 }); await reload;
    assert.equal(store.files[0].name, 'fresh');
    assert.equal(store.cwd, 'Home/current');
});
