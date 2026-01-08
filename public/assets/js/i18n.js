const i18n = Vue.reactive({
    locale: 'en',
    messages: {},
    
    async load(locale) {
        try {
            const res = await fetch(`${baseUrl}assets/i18n/${locale}.json`);
            this.messages = await res.json();
            this.locale = locale;
        } catch(e) {
            console.error('Failed to load locale', e);
        }
    },
    
    t(key, params = {}) {
        let str = this.messages[key] || key;
        Object.keys(params).forEach(k => {
            str = str.replace(`{${k}}`, params[k]);
        });
        return str;
    }
});
