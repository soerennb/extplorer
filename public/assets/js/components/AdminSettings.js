const AdminSettings = {
    props: {
        initialTab: {
            type: String,
            default: 'email'
        },
        onTabChange: {
            type: Function,
            default: null
        }
    },
    template: `
    <div>
        <div v-if="error" class="alert alert-danger small">{{ error }}</div>
        <div v-if="!settings" class="text-center text-muted py-5">Loading settings…</div>
        <div v-else>
            <ul class="nav nav-pills mb-3 flex-wrap gap-1">
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'email'}" href="#" @click.prevent="settingsTab = 'email'">Email</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'transfers'}" href="#" @click.prevent="settingsTab = 'transfers'">Transfers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'sharing'}" href="#" @click.prevent="settingsTab = 'sharing'">Sharing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'governance'}" href="#" @click.prevent="settingsTab = 'governance'">Governance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'mounts'}" href="#" @click.prevent="settingsTab = 'mounts'">Mounts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'logging'}" href="#" @click.prevent="settingsTab = 'logging'">Logging</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'security'}" href="#" @click.prevent="settingsTab = 'security'">Security</a>
                </li>
            </ul>

            <form @submit.prevent="saveSettings">
                <div v-if="settingsTab === 'email'">
                    <h6 class="border-bottom pb-2 mb-3">Email Configuration</h6>
                    <div v-if="emailValidation.message" class="alert small" :class="emailValidation.ok ? 'alert-success' : 'alert-danger'">
                        {{ emailValidation.message }}
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Protocol</label>
                            <select class="form-select form-select-sm" v-model="settings.email_protocol">
                                <option value="smtp">SMTP</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="mail">PHP mail()</option>
                            </select>
                        </div>
                        <div class="col-md-8" v-if="settings.email_protocol === 'sendmail'">
                            <label class="form-label small fw-bold">Sendmail Path</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.sendmail_path" placeholder="/usr/sbin/sendmail">
                        </div>
                    </div>

                    <div v-if="settings.email_protocol === 'smtp'" class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">SMTP Host</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_host" placeholder="smtp.example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Port</label>
                            <input type="number" class="form-control form-control-sm" v-model.number="settings.smtp_port" placeholder="587">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_user">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" class="form-control form-control-sm" v-model="settings.smtp_pass" placeholder="********">
                            <div class="form-text">Leave masked to keep the existing password.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Encryption</label>
                            <select class="form-select form-select-sm" v-model="settings.smtp_crypto">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">From Email</label>
                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">From Name</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                        </div>
                    </div>

                    <div v-if="settings.email_protocol !== 'smtp'" class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">From Email</label>
                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">From Name</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'transfers'">
                    <h6 class="border-bottom pb-2 mb-3">Transfers</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Default Expiry (Days)</label>
                            <input type="number" min="1" class="form-control form-control-sm" v-model.number="settings.default_transfer_expiry">
                            <div class="form-text">Used when no expiry is provided.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Max Expiry (Days)</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.transfer_max_expiry_days">
                            <div class="form-text">Upper bound for all transfers.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminTransferNotifyDownload" v-model="settings.transfer_default_notify_download">
                                <label class="form-check-label small fw-bold" for="adminTransferNotifyDownload">Notify On Download (Default)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'sharing'">
                    <h6 class="border-bottom pb-2 mb-3">Share Links</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Default Expiry (Days)</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_default_expiry_days">
                            <div class="form-text">Used when expiry is required but not provided.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Max Expiry (Days)</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_max_expiry_days">
                            <div class="form-text">Upper bound for all share links.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminShareRequireExpiry" v-model="settings.share_require_expiry">
                                <label class="form-check-label small fw-bold" for="adminShareRequireExpiry">Require Expiry</label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminShareRequirePassword" v-model="settings.share_require_password">
                                <label class="form-check-label small fw-bold" for="adminShareRequirePassword">Require Password</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'governance'">
                    <h6 class="border-bottom pb-2 mb-3">Uploads & Quotas</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Max Upload Size (MB)</label>
                            <input type="number" min="0" max="10240" class="form-control form-control-sm" v-model.number="settings.upload_max_file_mb">
                            <div class="form-text">0 disables the limit. Applies to single and chunked uploads.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Per-User Quota (MB)</label>
                            <input type="number" min="0" max="102400" class="form-control form-control-sm" v-model.number="settings.quota_per_user_mb">
                            <div class="form-text">0 disables the quota. Applies to the user home directory.</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'mounts'">
                    <h6 class="border-bottom pb-2 mb-3">External Mounts</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Allowlist (one path per line)</label>
                            <textarea class="form-control form-control-sm" rows="4" v-model="settings.mount_root_allowlist_text" placeholder="/srv/data&#10;/mnt/storage"></textarea>
                            <div class="form-text">Only paths under these roots can be mounted. Leave empty to disable external mounts.</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'logging'">
                    <h6 class="border-bottom pb-2 mb-3">Audit Logging</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Retention Count</label>
                            <input type="number" min="100" max="20000" step="100" class="form-control form-control-sm" v-model.number="settings.log_retention_count">
                            <div class="form-text">How many recent audit log entries to keep (100-20000).</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'security'">
                    <h6 class="border-bottom pb-2 mb-3">Session Security</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Idle Timeout (Minutes)</label>
                            <input type="number" min="0" max="1440" class="form-control form-control-sm" v-model.number="settings.session_idle_timeout_minutes">
                            <div class="form-text">0 disables idle timeout. Applies to API and UI sessions.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <div v-if="settingsDirty" class="text-warning small mb-2 text-start">
                        You have unsaved settings changes.
                    </div>
                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="validateEmailSettings" :disabled="!settings || isValidatingEmail">
                        {{ isValidatingEmail ? 'Validating…' : 'Validate' }}
                    </button>
                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="testEmail" :disabled="!settings || isTestingEmail">
                        {{ isTestingEmail ? 'Sending…' : 'Send Test Email' }}
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="!settingsDirty || isSavingSettings">
                        {{ isSavingSettings ? 'Saving…' : 'Save Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    `,
    data() {
        return {
            settings: null,
            settingsOriginal: null,
            settingsTab: this.initialTab || 'email',
            emailValidation: { ok: null, message: '', timeoutId: null },
            error: null,
            isSavingSettings: false,
            isValidatingEmail: false,
            isTestingEmail: false
        };
    },
    computed: {
        settingsDirty() {
            if (!this.settings || !this.settingsOriginal) return false;
            try {
                return JSON.stringify(this.settings) !== JSON.stringify(this.settingsOriginal);
            } catch (e) {
                return false;
            }
        }
    },
    watch: {
        settingsTab(newTab) {
            if (this.onTabChange) {
                this.onTabChange(newTab);
            }
        },
        initialTab(newTab) {
            if (newTab && newTab !== this.settingsTab) {
                this.settingsTab = newTab;
            }
        }
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        async loadSettings() {
            this.error = null;
            try {
                this.settings = await Api.get('settings');
                if (!this.settings.mount_root_allowlist_text && Array.isArray(this.settings.mount_root_allowlist)) {
                    this.settings.mount_root_allowlist_text = this.settings.mount_root_allowlist.join('\n');
                }
                if (!this.settings.email_protocol) {
                    this.settings.email_protocol = 'smtp';
                }
                if (!this.settings.sendmail_path) {
                    this.settings.sendmail_path = '/usr/sbin/sendmail';
                }
                this.normalizeSettings();
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.clearEmailValidation();
            } catch (e) {
                this.error = e.message;
            }
        },
        normalizeSettings() {
            if (!this.settings) return;
            if (typeof this.settings.log_retention_count !== 'number' || Number.isNaN(this.settings.log_retention_count)) {
                this.settings.log_retention_count = 2000;
            }
            if (typeof this.settings.transfer_max_expiry_days !== 'number' || Number.isNaN(this.settings.transfer_max_expiry_days)) {
                this.settings.transfer_max_expiry_days = 30;
            }
            if (typeof this.settings.default_transfer_expiry !== 'number' || Number.isNaN(this.settings.default_transfer_expiry)) {
                this.settings.default_transfer_expiry = 7;
            }
            if (typeof this.settings.transfer_default_notify_download !== 'boolean') {
                this.settings.transfer_default_notify_download = false;
            }
            if (typeof this.settings.share_default_expiry_days !== 'number' || Number.isNaN(this.settings.share_default_expiry_days)) {
                this.settings.share_default_expiry_days = 7;
            }
            if (typeof this.settings.share_max_expiry_days !== 'number' || Number.isNaN(this.settings.share_max_expiry_days)) {
                this.settings.share_max_expiry_days = 30;
            }
            if (typeof this.settings.share_require_expiry !== 'boolean') {
                this.settings.share_require_expiry = false;
            }
            if (typeof this.settings.share_require_password !== 'boolean') {
                this.settings.share_require_password = false;
            }
            if (typeof this.settings.session_idle_timeout_minutes !== 'number' || Number.isNaN(this.settings.session_idle_timeout_minutes)) {
                this.settings.session_idle_timeout_minutes = 120;
            }
            if (typeof this.settings.upload_max_file_mb !== 'number' || Number.isNaN(this.settings.upload_max_file_mb)) {
                this.settings.upload_max_file_mb = 0;
            }
            if (typeof this.settings.quota_per_user_mb !== 'number' || Number.isNaN(this.settings.quota_per_user_mb)) {
                this.settings.quota_per_user_mb = 0;
            }
        },
        async saveSettings() {
            if (!this.settingsDirty) return;
            this.isSavingSettings = true;
            try {
                await Api.post('settings', this.settings);
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.toastSuccess('Settings saved.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to save settings.');
            } finally {
                this.isSavingSettings = false;
            }
        },
        async testEmail() {
            if (!this.settings) return;
            const email = await this.promptForEmail();
            if (!email) return;
            this.isTestingEmail = true;
            try {
                await Api.post('settings/test-email', { ...this.settings, email });
                this.setEmailValidation(true, 'Test email sent successfully.');
                this.toastSuccess('Test email sent.');
            } catch (e) {
                this.setEmailValidation(false, 'Test failed: ' + e.message);
                this.toastError('Test email failed.');
            } finally {
                this.isTestingEmail = false;
            }
        },
        async validateEmailSettings() {
            if (!this.settings) return;
            this.isValidatingEmail = true;
            try {
                const res = await Api.post('settings/validate-email', this.settings);
                this.setEmailValidation(true, res.message || 'Validation successful');
            } catch (e) {
                this.setEmailValidation(false, 'Validation failed: ' + e.message);
            } finally {
                this.isValidatingEmail = false;
            }
        },
        setEmailValidation(ok, message) {
            this.clearEmailValidation();
            this.emailValidation.ok = ok;
            this.emailValidation.message = message;
            this.emailValidation.timeoutId = setTimeout(() => {
                this.clearEmailValidation();
            }, 5000);
        },
        clearEmailValidation() {
            if (this.emailValidation.timeoutId) {
                clearTimeout(this.emailValidation.timeoutId);
            }
            this.emailValidation.ok = null;
            this.emailValidation.message = '';
            this.emailValidation.timeoutId = null;
        },
        toastBase() {
            return Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        },
        toastSuccess(message) {
            this.toastBase().fire({ icon: 'success', title: message });
        },
        toastError(message) {
            this.toastBase().fire({ icon: 'error', title: message });
        },
        async promptForEmail() {
            const result = await Swal.fire({
                title: 'Send test email',
                input: 'email',
                inputLabel: 'Recipient email',
                inputPlaceholder: 'name@example.com',
                showCancelButton: true,
                confirmButtonText: 'Send',
                inputValidator: (value) => {
                    if (!value) return 'Email is required';
                    return null;
                }
            });
            if (!result.isConfirmed) return null;
            return result.value;
        }
    }
};

