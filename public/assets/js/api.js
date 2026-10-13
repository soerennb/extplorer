const Api = {
    mutationQueue: Promise.resolve(),
    csrfRevision: 0,
    route(path) {
        return new URL(path, window.routeBaseUrl || window.baseUrl).href;
    },
    serializeMutation(send) {
        const pending = this.mutationQueue.then(send, send);
        this.mutationQueue = pending.catch(() => {});
        return pending;
    },
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
                text: (window.i18n && i18n.t('step_up_description')) || 'Enter your current password and, when enabled, your authenticator code.',
                html: `
                    <input id="step-up-password" type="password" class="swal2-input" autocomplete="current-password" placeholder="${(window.i18n && i18n.t('current_password')) || 'Current password'}">
                    <input id="step-up-code" type="text" inputmode="numeric" class="swal2-input" autocomplete="one-time-code" maxlength="8" placeholder="${(window.i18n && i18n.t('authenticator_code')) || 'Authenticator code (if enabled)'}">
                `,
                showCancelButton: true,
                confirmButtonText: (window.i18n && i18n.t('step_up_confirm')) || 'Continue',
                cancelButtonText: (window.i18n && i18n.t('cancel')) || 'Cancel',
                allowOutsideClick: false,
                preConfirm: () => {
                    const password = document.getElementById('step-up-password')?.value || '';
                    const code = document.getElementById('step-up-code')?.value || '';
                    if (!password) {
                        Swal.showValidationMessage((window.i18n && i18n.t('step_up_password_required')) || 'Current password is required.');
                        return false;
                    }
                    return { password, code };
                }
            });

            if (!result.isConfirmed || !result.value || typeof result.value.password !== 'string') {
                return null;
            }

            const response = await this.request('security/step-up', 'POST', {
                action,
                password: result.value.password,
                code: result.value.code || ''
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
            ? this.route('login') + '?' + expiredParam + 'return=' + encodeURIComponent(currentReturn || '/')
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
        const error = new Error(this.sessionExpiredMessage(payload));
        error.name = 'SessionExpiredError';
        throw error;
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
    get(endpoint, params = {}) { return this.read('api/' + endpoint, params); },
    async read(endpoint, params = {}) {
        const url = new URL(this.route(endpoint));
        Object.keys(params).forEach((key) => url.searchParams.append(key, params[key]));
        await this.mutationQueue;
        const revision = this.csrfRevision;
        let res;
        try { res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }); }
        catch (error) { throw new Error(this.genericNetworkError()); }
        if (revision === this.csrfRevision) this.refreshCsrfToken(res);
        const payload = await this.parseResponseBody(res);
        if (!res.ok) return this.handleNonOk(res, payload);
        return payload ?? {};
    },
    post(endpoint, data = {}, options = {}) { return this.request(endpoint, 'POST', data, null, false, options); },
    put(endpoint, data = {}, options = {}) { return this.request(endpoint, 'PUT', data, null, false, options); },
    delete(endpoint, data = {}, options = {}) { return this.request(endpoint, 'DELETE', data, null, false, options); },
    async recoverCsrf() {
        try {
            const response = await fetch(this.route('api/profile/details'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            this.refreshCsrfToken(response);
        } catch (error) { /* The next request will report an unavailable connection. */ }
    },
    async request(endpoint, method, data = {}, stepUpToken = null, stepUpRetried = false, options = {}) {
        const { res, payload } = await this.serializeMutation(async () => {
            const headers = { 'Content-Type': options.contentType || 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
            if (stepUpToken) headers['X-Extplorer-Step-Up'] = stepUpToken;
            this.csrfRevision++;
            let res;
            try {
                res = await fetch(this.route('api/' + endpoint), {
                    method, headers, body: options.contentType ? data : JSON.stringify(data), signal: options.signal
                });
            } catch (error) {
                if (error.name === 'AbortError') { await this.recoverCsrf(); throw error; }
                throw new Error(this.genericNetworkError());
            }
            this.refreshCsrfToken(res);
            return { res, payload: await this.parseResponseBody(res) };
        });
        if (!res.ok) {
            // The network queue is released before prompting: step-up itself is a mutation.
            if (!stepUpRetried && this.isStepUpRequiredPayload(payload, res.status)) {
                const token = await this.requestStepUp(payload.action);
                if (token) return this.request(endpoint, method, data, token, true, options);
            }
            return this.handleNonOk(res, payload);
        }
        return payload ?? {};
    },
    async upload(endpoint, form, { signal, onProgress = () => {} } = {}) {
        const { res, payload } = await this.serializeMutation(() => new Promise((resolve, reject) => {
            if (signal?.aborted) { reject(new DOMException('Upload canceled', 'AbortError')); return; }
            const xhr = new XMLHttpRequest();
            const abort = () => xhr.abort();
            const cleanup = () => signal?.removeEventListener('abort', abort);
            xhr.open('POST', this.route(endpoint));
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (window.csrfHash) xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfHash);
            this.csrfRevision++;
            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) onProgress(event.loaded / event.total);
            };
            xhr.onload = () => {
                cleanup();
                const res = { status: xhr.status, ok: xhr.status >= 200 && xhr.status < 300,
                    headers: { get: (name) => xhr.getResponseHeader(name) } };
                this.refreshCsrfToken(res);
                let payload;
                try { payload = xhr.responseText ? JSON.parse(xhr.responseText) : {}; }
                catch (error) { payload = { message: xhr.responseText }; }
                resolve({ res, payload });
            };
            xhr.onerror = () => { cleanup(); reject(new Error(this.genericNetworkError())); };
            xhr.onabort = async () => { cleanup(); await this.recoverCsrf(); reject(new DOMException('Upload canceled', 'AbortError')); };
            signal?.addEventListener('abort', abort, { once: true });
            xhr.send(form);
        }));
        if (!res.ok) return this.handleNonOk(res, payload);
        return payload;
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
            if (reason?.name === 'SessionExpiredError' || reason?.name === 'AbortError') return;
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
