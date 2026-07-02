const i18n = Vue.reactive({
    locale: 'en',
    fallbackLocale: 'en',
    messages: {},
    fallbackMessages: {},
    availableLocales: ['en', 'de', 'fr', 'sk'],
    availableLocaleOptions: [
        { code: 'en', labelKey: 'language_english', labelFallback: 'English' },
        { code: 'de', labelKey: 'language_german', labelFallback: 'Deutsch' },
        { code: 'fr', labelKey: 'language_french', labelFallback: 'Français' },
        { code: 'sk', labelKey: 'language_slovak', labelFallback: 'Slovenčina' }
    ],
    storageKey: 'extplorer_locale',

    async loadManifest() {
        try {
            const v = window.appVersion || Date.now();
            const res = await fetch(`${baseUrl}assets/i18n/locales.json?v=${v}`);
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const locales = await res.json();
            if (!Array.isArray(locales)) {
                throw new Error('Invalid locale manifest');
            }

            const normalized = locales
                .filter((locale) => locale && typeof locale.code === 'string' && locale.code)
                .map((locale) => ({
                    code: locale.code,
                    labelKey: locale.labelKey || `language_${locale.code}`,
                    labelFallback: locale.labelFallback || locale.code
                }));

            if (normalized.length > 0) {
                this.availableLocaleOptions = normalized;
                this.availableLocales = normalized.map((locale) => locale.code);
            }
        } catch (e) {
            console.error('Failed to load locale manifest', e);
        }
    },

    async fetchMessages(locale) {
        const v = window.appVersion || Date.now();
        const res = await fetch(`${baseUrl}assets/i18n/${locale}.json?v=${v}`);
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        return await res.json();
    },

    async load(locale) {
        try {
            const nextLocale = this.availableLocales.includes(locale) ? locale : 'en';
            this.fallbackMessages = await this.fetchMessages(this.fallbackLocale);
            this.messages = nextLocale === this.fallbackLocale
                ? this.fallbackMessages
                : { ...this.fallbackMessages, ...await this.fetchMessages(nextLocale) };
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
        await this.loadManifest();
        const locale = this.preferredLocale();
        await this.load(locale);
        return this.locale;
    },
    async setLocale(locale) {
        await this.load(locale);
        return this.locale;
    },
    
    t(key, params = {}) {
        let str = this.messages[key] || this.fallbackMessages[key] || key;
        if (str === key && typeof window !== 'undefined' && window.appEnvironment === 'development') {
            console.warn(`Missing translation: ${key}`);
        }
        Object.keys(params).forEach(k => {
            str = str.split(`{${k}}`).join(params[k]);
        });
        return str;
    }
});

// Make i18n accessible via window for pages that guard on window.i18n.
if (typeof window !== 'undefined') {
    window.i18n = i18n;
}
