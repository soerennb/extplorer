const Api = {
    genericServerError() {
        if (window.i18n && typeof window.i18n.t === 'function') {
            return window.i18n.t('unexpected_error_generic', 'An unexpected server error occurred. Please try again.');
        }
        return 'An unexpected server error occurred. Please try again.';
    },
    genericNetworkError() {
        if (window.i18n && typeof window.i18n.t === 'function') {
            return window.i18n.t('network_error_generic', 'Unable to reach the server. Please check your connection and try again.');
        }
        return 'Unable to reach the server. Please check your connection and try again.';
    },
    async parseResponseBody(res) {
        const contentType = (res.headers.get('content-type') || '').toLowerCase();
        const isJson = contentType.includes('application/json') || contentType.includes('+json');

        if (isJson) {
            try {
                return await res.json();
            } catch (e) {
                return null;
            }
        }

        const text = await res.text();
        if (!text) {
            return null;
        }

        try {
            return JSON.parse(text);
        } catch (e) {
            return { message: text };
        }
    },
    errorFromPayload(payload, fallback = '') {
        if (payload && typeof payload === 'object') {
            return payload.messages?.error || payload.message || payload.error || payload.title || fallback || this.genericServerError();
        }

        if (typeof payload === 'string' && payload.trim() !== '') {
            return payload.trim();
        }

        return fallback || this.genericServerError();
    },
    async readErrorMessage(res, fallback = '') {
        const payload = await this.parseResponseBody(res);
        const statusFallback = fallback || (res.status ? `HTTP ${res.status}` : '');
        return this.errorFromPayload(payload, statusFallback);
    },
    async get(endpoint, params = {}) {
        const url = new URL('api/' + endpoint, window.baseUrl);
        Object.keys(params).forEach((key) => url.searchParams.append(key, params[key]));

        let res;
        try {
            res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        } catch (e) {
            throw new Error(this.genericNetworkError());
        }

        const payload = await this.parseResponseBody(res);
        if (!res.ok) {
            throw new Error(this.errorFromPayload(payload, res.statusText || this.genericServerError()));
        }

        return payload ?? {};
    },
    async post(endpoint, data = {}) {
        return this.request(endpoint, 'POST', data);
    },
    async put(endpoint, data = {}) {
        return this.request(endpoint, 'PUT', data);
    },
    async delete(endpoint, data = {}) {
        return this.request(endpoint, 'DELETE', data);
    },
    async request(endpoint, method, data = {}) {
        const url = window.baseUrl + 'api/' + endpoint;
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (window.csrfTokenName && window.csrfHash) {
            headers['X-CSRF-TOKEN'] = window.csrfHash;
        }

        let res;
        try {
            res = await fetch(url, {
                method: method,
                headers: headers,
                body: JSON.stringify(data)
            });
        } catch (e) {
            throw new Error(this.genericNetworkError());
        }

        const payload = await this.parseResponseBody(res);
        if (!res.ok) {
            throw new Error(this.errorFromPayload(payload, res.statusText || this.genericServerError()));
        }

        return payload ?? {};
    },
    initGlobalErrorHandlers() {
        if (window.__extplorerGlobalErrorHandlers) {
            return;
        }
        window.__extplorerGlobalErrorHandlers = true;

        const showError = (message) => {
            if (window.Swal && typeof Swal.fire === 'function') {
                Swal.fire((window.i18n && i18n.t('error')) || 'Error', message || this.genericServerError(), 'error');
                return;
            }
            console.error(message || this.genericServerError());
        };

        window.addEventListener('unhandledrejection', (event) => {
            const reason = event && event.reason ? event.reason : null;
            const message = this.errorFromPayload(reason, this.genericServerError());
            showError(message);
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
        });

        window.addEventListener('error', (event) => {
            const msg = event && event.message ? event.message : this.genericServerError();
            showError(msg);
        });
    }
};

Api.initGlobalErrorHandlers();
