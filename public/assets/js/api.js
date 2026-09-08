const Api = {
    sessionExpiredDialogOpen: false,
    stepUpDialogOpen: false,
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
    localizedErrorFromPayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return '';
        }

        const translationKeys = {
            current_password_incorrect: 'current_password_incorrect',
            invalid_remote_endpoint_allowlist: 'admin_settings_remote_endpoint_allowlist_invalid'
        };
        const key = translationKeys[payload.error];
        if (!key || !window.i18n || typeof window.i18n.t !== 'function') {
            return '';
        }

        const translated = window.i18n.t(key);
        return translated && translated !== key ? translated : '';
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
            const localizedMessage = this.localizedErrorFromPayload(payload);
            if (localizedMessage) {
                return localizedMessage;
            }
            return payload.messages?.error || payload.message || payload.error || payload.title || fallback || this.genericServerError();
        }

        if (typeof payload === 'string' && payload.trim() !== '') {
            return payload.trim();
        }

        return fallback || this.genericServerError();
    },
    isSessionExpiredPayload(payload, status = 0) {
        return status === 401 && payload && typeof payload === 'object'
            && (payload.code === 'session_expired' || payload.code === 'auth_required');
    },
    isStepUpRequiredPayload(payload, status = 0) {
        return status === 428 && payload && typeof payload === 'object'
            && payload.error === 'step_up_required'
            && typeof payload.action === 'string'
            && payload.action !== '';
    },
    async requestStepUp(action) {
        if (this.stepUpDialogOpen) {
            return null;
        }

        this.stepUpDialogOpen = true;
        try {
            if (!window.Swal || typeof Swal.fire !== 'function') {
                return null;
            }

            const result = await Swal.fire({
                icon: 'warning',
                title: (window.i18n && i18n.t('step_up_title')) || 'Confirm your identity',
                text: (window.i18n && i18n.t('step_up_description')) || 'Enter your current password to continue.',
                input: 'password',
                inputAttributes: {
                    autocomplete: 'current-password',
                    autocapitalize: 'none',
                    autocorrect: 'off'
                },
                inputPlaceholder: (window.i18n && i18n.t('current_password')) || 'Current password',
                showCancelButton: true,
                confirmButtonText: (window.i18n && i18n.t('step_up_confirm')) || 'Continue',
                cancelButtonText: (window.i18n && i18n.t('cancel')) || 'Cancel',
                allowOutsideClick: false,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage((window.i18n && i18n.t('step_up_password_required')) || 'Current password is required.');
                    }
                    return password;
                }
            });

            if (!result.isConfirmed || typeof result.value !== 'string' || result.value === '') {
                return null;
            }

            const response = await this.request('security/step-up', 'POST', {
                action,
                password: result.value
            }, null, true);
            return typeof response.token === 'string' ? response.token : null;
        } finally {
            this.stepUpDialogOpen = false;
        }
    },
    sessionExpiredMessage(payload) {
        if (payload && payload.code === 'auth_required') {
            return (window.i18n && i18n.t('auth_required_message'))
                || 'Please sign in to continue.';
        }
        return (window.i18n && i18n.t('session_expired_message'))
            || 'Your session has expired. Sign in again to continue.';
    },
    handleSessionExpired(payload = {}) {
        if (this.sessionExpiredDialogOpen) {
            return;
        }
        this.sessionExpiredDialogOpen = true;

        const title = (window.i18n && i18n.t('session_expired_title')) || 'Session expired';
        const message = this.sessionExpiredMessage(payload);
        const confirmText = (window.i18n && i18n.t('sign_in_again')) || 'Sign in again';
        const currentReturn = window.location.pathname + window.location.search;
        const expiredParam = payload.code === 'session_expired' ? 'expired=1&' : '';
        const loginUrl = window.baseUrl
            ? window.baseUrl + 'login?' + expiredParam + 'return=' + encodeURIComponent(currentReturn || '/')
            : (payload.login_url || 'login');

        if (window.Swal && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'warning',
                title,
                text: message,
                confirmButtonText: confirmText,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showCancelButton: false
            }).then(() => {
                window.location.href = loginUrl;
            });
            return;
        }

        window.location.href = loginUrl;
    },
    blockForSessionExpired(payload = {}) {
        this.handleSessionExpired(payload);
        return new Promise(() => {});
    },
    async handleNonOk(res, payload, fallback = '') {
        if (this.isSessionExpiredPayload(payload, res.status)) {
            return this.blockForSessionExpired(payload);
        }
        throw new Error(this.errorFromPayload(payload, fallback || res.statusText || this.genericServerError()));
    },
    refreshCsrfToken(res) {
        if (!res || !res.headers || typeof res.headers.get !== 'function') {
            return;
        }

        const token = res.headers.get('X-CSRF-HASH');
        if (token) {
            window.csrfHash = token;
        }
    },
    async readErrorMessage(res, fallback = '') {
        const payload = await this.parseResponseBody(res);
        if (this.isSessionExpiredPayload(payload, res.status)) {
            return this.blockForSessionExpired(payload);
        }
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

        this.refreshCsrfToken(res);
        const payload = await this.parseResponseBody(res);
        if (!res.ok) {
            return this.handleNonOk(res, payload);
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
    async request(endpoint, method, data = {}, stepUpToken = null, stepUpRetried = false) {
        const url = window.baseUrl + 'api/' + endpoint;
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (window.csrfTokenName && window.csrfHash) {
            headers['X-CSRF-TOKEN'] = window.csrfHash;
        }
        if (stepUpToken) {
            headers['X-Extplorer-Step-Up'] = stepUpToken;
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

        this.refreshCsrfToken(res);
        const payload = await this.parseResponseBody(res);
        if (!res.ok) {
            if (!stepUpRetried && this.isStepUpRequiredPayload(payload, res.status)) {
                const token = await this.requestStepUp(payload.action);
                if (token) {
                    return this.request(endpoint, method, data, token, true);
                }
            }
            return this.handleNonOk(res, payload);
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
