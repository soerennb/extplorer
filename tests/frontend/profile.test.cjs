const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

test('profile modules initialize together and share mount validation state', () => {
    const context = vm.createContext({
        Vue: { ref: (value) => ({ value }), reactive: (value) => value,
            computed: (getter) => ({ get value() { return getter(); } }), watch() {}, onMounted() {}, nextTick: async () => {} },
        window: { userPermissions: ['*'] }, i18n: { t: (key) => key, locale: 'en' },
        console, Api: {}, localStorage: { getItem() { return null; } }
    });
    for (const file of ['profile/mounts.js', 'profile/webdav.js', 'profile/security.js', 'components/UserProfile.js']) {
        vm.runInContext(fs.readFileSync('public/assets/js/' + file, 'utf8'), context);
    }
    const profile = vm.runInContext('UserProfile.setup()', context);
    assert.equal(profile.activeTab.value, 'general');
    assert.equal(profile.canMount.value, true);
    assert.equal(profile.canSubmitMount.value, false);
    assert.equal(profile.canTestMount, profile.canSubmitMount);
    profile.mountForm.name = 'Documents';
    profile.mountForm.config.path = '/data/documents';
    assert.equal(profile.canSubmitMount.value, true);
    assert.equal(profile.webDavCredentials.value.length, 0);
    assert.equal(profile.passwordInvalid.value, true);
    profile.passwordForm.current = 'current';
    profile.passwordForm.new = 'Replacement!123';
    profile.passwordForm.confirm = 'Replacement!123';
    assert.equal(profile.passwordInvalid.value, false);
});
