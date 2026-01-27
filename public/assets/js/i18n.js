const i18n = Vue.reactive({
    locale: 'en',
    messages: {},
    availableLocales: ['en', 'de', 'fr'],
    storageKey: 'extplorer_locale',
    
    async load(locale) {
        try {
            const nextLocale = this.availableLocales.includes(locale) ? locale : 'en';
            const v = window.appVersion || Date.now();
            const res = await fetch(`${baseUrl}assets/i18n/${nextLocale}.json?v=${v}`);
            this.messages = await res.json();
            this.locale = nextLocale;
            try {
                localStorage.setItem(this.storageKey, nextLocale);
            } catch (e) {
                // Ignore storage failures (e.g., private mode)
            }
            if (typeof document !== 'undefined') {
                document.documentElement.setAttribute('lang', nextLocale);
            }
        } catch(e) {
            console.error('Failed to load locale', e);
        }
    },
    preferredLocale() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored && this.availableLocales.includes(stored)) {
                return stored;
            }
        } catch (e) {
            // Ignore storage failures
        }
        return 'en';
    },
    async init() {
        const locale = this.preferredLocale();
        await this.load(locale);
        return this.locale;
    },
    async setLocale(locale) {
        await this.load(locale);
        return this.locale;
    },
    
    t(key, params = {}) {
        let str = this.messages[key] || key;
        Object.keys(params).forEach(k => {
            str = str.replace(`{${k}}`, params[k]);
        });
        return str;
    }
});

// Make i18n accessible via window for pages that guard on window.i18n.
if (typeof window !== 'undefined') {
    window.i18n = i18n;
}
