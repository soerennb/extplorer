const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// SweetAlert2 renders `title` and the positional second argument as HTML.
class FakeSwal {
    static fire(...args) { return { owner: this, args }; }
    static mixin(params) { const Parent = this; return class extends Parent { static mixinParams = params; }; }
}

function load() {
    const Swal = class extends FakeSwal {};
    const context = vm.createContext({ window: { Swal } });
    vm.runInContext(fs.readFileSync('public/assets/js/safe-alerts.js', 'utf8'), context);
    return Swal;
}

test('positional alerts render server messages as text', () => {
    const Swal = load();
    const result = Swal.fire('Error', '<img src=x onerror=alert(1)>', 'error');
    assert.deepEqual({ ...result.args[0] }, { titleText: 'Error', text: '<img src=x onerror=alert(1)>', icon: 'error' });
    assert.equal(result.args.length, 1);
});

test('object titles render as text, including on toast mixins', () => {
    const Swal = load();
    const toast = Swal.mixin({ toast: true });
    const result = toast.fire({ icon: 'error', title: '<b>file.html</b>' });
    assert.equal(result.owner, toast);
    assert.deepEqual({ ...result.args[0] }, { icon: 'error', titleText: '<b>file.html</b>' });
});

test('explicit html templates and titleText remain unchanged', () => {
    const Swal = load();
    const params = { title: 'ignored', titleText: 'Permissions', html: '<input id="swal-i1">' };
    assert.equal(Swal.fire(params).args[0], params);
});

test('the guard is idempotent', () => {
    const Swal = load();
    const context = vm.createContext({ window: { Swal } });
    vm.runInContext(fs.readFileSync('public/assets/js/safe-alerts.js', 'utf8'), context);
    assert.deepEqual({ ...Swal.fire('A', 'B').args[0] }, { titleText: 'A', text: 'B', icon: undefined });
});
