const ShareModal = {
    template: `
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-share-line me-2"></i> {{ t('share_title') || 'Share' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="loading" class="text-center"><div class="spinner-border"></div></div>
                    
                    <div v-else-if="currentShare">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" :value="shareUrl" readonly id="shareUrlInput">
                            <button class="btn btn-outline-primary" @click="copyLink" title="Copy link"><i class="ri-file-copy-line"></i></button>
                            <button class="btn btn-outline-secondary" @click="openLink" title="Open link"><i class="ri-external-link-line"></i></button>
                        </div>
                        
                        <div class="alert alert-light border small">
                            <strong>{{ t('created') }}:</strong> {{ formatDate(currentShare.created_at) }}<br>
                            <strong>{{ t('share_mode_label') || 'Mode' }}:</strong> {{ modeLabel(currentShare.mode) }}<br>
                            <span v-if="currentShare.expires_at">
                                <strong>{{ t('expires') }}:</strong> {{ formatDate(currentShare.expires_at) }}
                                <span class="text-muted">({{ expiryHuman(currentShare.expires_at) }})</span><br>
                            </span>
                            <span v-if="currentShare.password_hash">
                                <strong>{{ t('password_protected') || 'Password Protected' }}</strong><br>
                            </span>
                            <strong>{{ t('downloads') || 'Downloads' }}:</strong> {{ currentShare.downloads }}
                        </div>

                        <button class="btn btn-danger w-100" :disabled="loading" @click="deleteShare">{{ t('stop_sharing') || 'Stop Sharing' }}</button>
                    </div>

                    <div v-else>
                        <p class="small text-muted">{{ t('share_desc') || 'Create a public link for this item.' }}</p>
                        
                        <div v-if="!isDirectory" class="d-grid mb-3">
                            <button class="btn btn-outline-primary btn-sm" @click="sendCopy">
                                <i class="ri-send-plane-fill me-2"></i> {{ t('send_files') || 'Send a Copy' }}
                            </button>
                        </div>

                        <div v-if="policyLoaded" class="alert alert-light border small">
                            <div v-if="policy.require_password">
                                <strong>Password is required by policy.</strong>
                            </div>
                            <div v-if="policy.require_expiry">
                                Expiry is required. Default: {{ policy.default_expiry_days }} day<span v-if="policy.default_expiry_days !== 1">s</span>.
                            </div>
                            <div>
                                Maximum expiry: {{ policy.max_expiry_days }} day<span v-if="policy.max_expiry_days !== 1">s</span>.
                            </div>
                            <div v-if="policy.allow_upload_mode">
                                Upload-mode shares are allowed for folders.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ t('share_mode_label') || 'Share Mode' }}</label>
                            <select class="form-select" v-model="form.mode">
                                <option v-for="opt in modeOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <div v-if="policy.allow_upload_mode && !isDirectory" class="form-text">
                                {{ t('share_mode_upload_folders_only', 'Upload mode is only available for folders.') }}
                            </div>
                        </div>

                        <div v-if="form.mode === 'upload'" class="alert alert-warning small">
                            <div class="fw-bold mb-1">{{ t('share_mode_upload_title', 'Upload-only share') }}</div>
                            <div>{{ t('share_mode_upload_desc', 'Recipients can upload files but cannot download existing content.') }}</div>
                            <div class="mt-1">
                                <span v-if="uploadPolicy.max_file_mb > 0">
                                    {{ t('share_mode_upload_max_file', 'Max file size: {max} MB', { max: uploadPolicy.max_file_mb }) }}
                                </span>
                                <span v-else>
                                    {{ t('share_mode_upload_no_max_file', 'No max file size configured.') }}
                                </span>
                            </div>
                            <div v-if="uploadPolicy.allowed_extensions_label" class="mt-1">
                                {{ t('share_mode_upload_allowed_types', 'Allowed types: {types}', { types: uploadPolicy.allowed_extensions_label }) }}
                            </div>
                            <div class="mt-1">
                                <span v-if="uploadPolicy.quota_mb > 0">
                                    {{ t('share_mode_upload_quota', 'Total quota: {quota} MB', { quota: uploadPolicy.quota_mb }) }}
                                </span>
                                <span v-else>
                                    {{ t('share_mode_upload_no_quota', 'No total quota configured.') }}
                                </span>
                            </div>
                            <div class="mt-1">
                                <span v-if="uploadPolicy.max_files > 0">
                                    {{ t('share_mode_upload_max_files', 'Max files: {count}', { count: uploadPolicy.max_files }) }}
                                </span>
                                <span v-else>
                                    {{ t('share_mode_upload_no_max_files', 'No max file count configured.') }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ policy.require_password ? (t('password') || 'Password') : (t('password_optional') || 'Password (Optional)') }}
                            </label>
                            <input type="password" class="form-control" v-model="form.password" :placeholder="policy.require_password ? '' : '***'">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ policy.require_expiry ? (t('expiration') || 'Expiration') : (t('expiration_optional') || 'Expiration (Optional)') }}
                            </label>
                            <select class="form-select" v-model="form.expiryDays">
                                <option v-if="!policy.require_expiry" :value="0">{{ t('never') || 'Never' }}</option>
                                <option v-for="days in expiryOptions" :key="days" :value="days">
                                    {{ days }} {{ days === 1 ? (t('day') || 'Day') : (t('days') || 'Days') }}
                                </option>
                            </select>
                            <div v-if="expiryPreview" class="form-text">Expires {{ expiryPreview }}</div>
                        </div>

                        <button class="btn btn-primary w-100" :disabled="loading" @click="createShare">{{ t('create_link') || 'Create Link' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    setup(props, context) {
        const { ref, reactive, computed } = Vue;
        const t = (k) => i18n.t(k);
        const loading = ref(false);
        const currentFile = ref(null);
        const currentShare = ref(null);
        const targetPath = ref('');
        const isDirectory = ref(false);
        const form = reactive({ password: '', expiryDays: 0, mode: 'read' });
        const policyLoaded = ref(false);
        const policy = reactive({
            require_password: false,
            require_expiry: false,
            default_expiry_days: 7,
            max_expiry_days: 30,
            allow_upload_mode: false,
            available_modes: ['read'],
            upload_policy: {
                max_file_mb: 0,
                allowed_extensions: [],
                allowed_extensions_label: '',
                quota_mb: 0,
                max_files: 0
            }
        });
        let modalInstance = null;

        const shareUrl = computed(() => {
            if (!currentShare.value) return '';
            return window.baseUrl + 's/' + currentShare.value.hash;
        });
        const uploadPolicy = computed(() => policy.upload_policy || {});
        const modeOptions = computed(() => {
            const options = [
                { value: 'read', label: t('share_mode_read') || 'Read-only (download)' }
            ];
            if (policy.allow_upload_mode && isDirectory.value) {
                options.push({ value: 'upload', label: t('share_mode_upload') || 'Upload-only (no download)' });
            }
            return options;
        });
        const expiryOptions = computed(() => {
            const baseOptions = [1, 3, 7, 14, 30, 60, 90, 180, 365];
            const maxDays = Number(policy.max_expiry_days || 30);
            const filtered = baseOptions.filter((d) => d <= maxDays);
            if (!filtered.includes(maxDays)) {
                filtered.push(maxDays);
            }
            if (!filtered.includes(policy.default_expiry_days)) {
                filtered.push(policy.default_expiry_days);
            }
            return Array.from(new Set(filtered))
                .filter((d) => d > 0)
                .sort((a, b) => a - b);
        });
        const expiryPreview = computed(() => {
            const days = Number(form.expiryDays || 0);
            if (days <= 0) return '';
            const ts = Math.floor(Date.now() / 1000) + (days * 86400);
            return formatDate(ts);
        });

        const formatDate = (ts) => new Date(ts * 1000).toLocaleString();
        const expiryHuman = (ts) => {
            if (!ts) return t('never') || 'Never';
            const now = Math.floor(Date.now() / 1000);
            const diff = ts - now;
            if (diff <= 0) return t('expired') || 'Expired';
            const days = Math.ceil(diff / 86400);
            if (days === 1) return 'in 1 day';
            if (days < 7) return `in ${days} days`;
            const weeks = Math.ceil(days / 7);
            if (weeks === 1) return 'in 1 week';
            return `in ${weeks} weeks`;
        };
        const modeLabel = (mode) => {
            if (mode === 'upload') return t('share_mode_upload') || 'Upload-only';
            if (mode === 'write') return t('share_mode_write') || 'Write';
            return t('share_mode_read') || 'Read';
        };

        const open = async (file) => {
            currentFile.value = file;
            targetPath.value = file.path;
            isDirectory.value = file.type === 'dir';
            form.password = '';
            form.expiryDays = 0;
            form.mode = 'read';
            currentShare.value = null;
            policyLoaded.value = false;
            
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('shareModal'));
            modalInstance.show();
            
            // Check if already shared
            loading.value = true;
            try {
                const policyRes = await Api.get('share/policy');
                policy.require_password = Boolean(policyRes.require_password);
                policy.require_expiry = Boolean(policyRes.require_expiry);
                policy.default_expiry_days = Number(policyRes.default_expiry_days || 7);
                policy.max_expiry_days = Number(policyRes.max_expiry_days || 30);
                policy.allow_upload_mode = Boolean(policyRes.allow_upload_mode);
                policy.available_modes = Array.isArray(policyRes.available_modes) ? policyRes.available_modes : ['read'];
                policy.upload_policy = policyRes.upload_policy || policy.upload_policy;
                policyLoaded.value = true;

                if (policy.require_expiry) {
                    form.expiryDays = policy.default_expiry_days;
                }

                const res = await Api.get('share/list');
                const existing = res.items.find(s => s.path === file.path);
                if (existing) {
                    currentShare.value = existing;
                    form.mode = existing.mode || 'read';
                }
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const createShare = async () => {
            loading.value = true;
            try {
                if (form.mode === 'upload' && !isDirectory.value) {
                    throw new Error(t('share_mode_upload_folders_only', 'Upload mode is only available for folders.'));
                }

                if (policy.require_password && !form.password) {
                    throw new Error('Password is required by policy.');
                }

                const allowedModes = Array.isArray(policy.available_modes) ? policy.available_modes : ['read'];
                if (!allowedModes.includes(form.mode)) {
                    throw new Error('Share mode is not allowed by policy.');
                }

                const maxDays = Number(policy.max_expiry_days || 30);
                let expiryDays = Number(form.expiryDays || 0);
                if (policy.require_expiry && expiryDays <= 0) {
                    expiryDays = Number(policy.default_expiry_days || 7);
                    form.expiryDays = expiryDays;
                }
                if (expiryDays > maxDays) {
                    expiryDays = maxDays;
                    form.expiryDays = maxDays;
                }

                let expires = null;
                if (expiryDays > 0) {
                    expires = Math.floor(Date.now() / 1000) + (expiryDays * 86400);
                }

                const res = await Api.post('share/create', {
                    path: targetPath.value,
                    password: form.password,
                    expires: expires,
                    mode: form.mode
                });
                currentShare.value = res.share;
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { loading.value = false; }
        };

        const deleteShare = async () => {
            if (!currentShare.value) return;
            loading.value = true;
            try {
                await Api.post('share/delete', { hash: currentShare.value.hash });
                currentShare.value = null;
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { loading.value = false; }
        };

        const copyLink = () => {
            const i = document.getElementById('shareUrlInput');
            if (!i) return;
            const value = i.value || shareUrl.value;
            if (navigator.clipboard && value) {
                navigator.clipboard.writeText(value);
            } else {
                i.select();
                document.execCommand('copy');
            }
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        };

        const openLink = () => {
            if (!shareUrl.value) return;
            window.open(shareUrl.value, '_blank', 'noopener');
        };

        const sendCopy = () => {
            if (!currentFile.value) return;
            if (modalInstance) modalInstance.hide();
            context.emit('transfer', currentFile.value);
        };

        return {
            open, loading, currentShare, form, createShare, deleteShare, shareUrl, copyLink, openLink, formatDate, expiryHuman, expiryPreview, expiryOptions, policy, policyLoaded,
            uploadPolicy, modeOptions, isDirectory, modeLabel, sendCopy,
            t
        };
    }
};
