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
        <div v-if="!settings" class="text-center text-muted py-5">{{ t('admin_settings_loading', 'Loading settings…') }}</div>
        <div v-else>
            <ul class="nav nav-pills mb-3 flex-wrap gap-1">
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'email'}" href="#" @click.prevent="settingsTab = 'email'">{{ t('admin_settings_tab_email', 'Email') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'transfers'}" href="#" @click.prevent="settingsTab = 'transfers'">{{ t('admin_settings_tab_transfers', 'Transfers') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'sharing'}" href="#" @click.prevent="settingsTab = 'sharing'">{{ t('admin_settings_tab_sharing', 'Sharing') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'governance'}" href="#" @click.prevent="settingsTab = 'governance'">{{ t('admin_settings_tab_governance', 'Governance') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'mounts'}" href="#" @click.prevent="settingsTab = 'mounts'">{{ t('admin_settings_tab_mounts', 'Mounts') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'logging'}" href="#" @click.prevent="settingsTab = 'logging'">{{ t('admin_settings_tab_logging', 'Logging') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{active: settingsTab === 'security'}" href="#" @click.prevent="settingsTab = 'security'">{{ t('admin_settings_tab_security', 'Security') }}</a>
                </li>
            </ul>

            <form @submit.prevent="saveSettings">
                <div v-if="settingsTab === 'email'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_email_heading', 'Email Configuration') }}</h6>
                    <div v-if="emailValidation.message" class="alert small" :class="emailValidation.ok ? 'alert-success' : 'alert-danger'">
                        {{ emailValidation.message }}
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_protocol', 'Protocol') }}</label>
                            <select class="form-select form-select-sm" v-model="settings.email_protocol">
                                <option value="smtp">{{ t('admin_settings_email_protocol_smtp', 'SMTP') }}</option>
                                <option value="sendmail">{{ t('admin_settings_email_protocol_sendmail', 'Sendmail') }}</option>
                                <option value="mail">{{ t('admin_settings_email_protocol_mail', 'PHP mail()') }}</option>
                            </select>
                        </div>
                        <div class="col-md-8" v-if="settings.email_protocol === 'sendmail'">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_sendmail_path', 'Sendmail Path') }}</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.sendmail_path" :placeholder="t('admin_settings_email_sendmail_placeholder', '/usr/sbin/sendmail')">
                        </div>
                    </div>

                    <div v-if="settings.email_protocol === 'smtp'" class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_smtp_host', 'SMTP Host') }}</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_host" :placeholder="t('admin_settings_email_smtp_host_placeholder', 'smtp.example.com')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_port', 'Port') }}</label>
                            <input type="number" class="form-control form-control-sm" v-model.number="settings.smtp_port" :placeholder="t('admin_settings_email_port_placeholder', '587')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_username', 'Username') }}</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_user">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_password', 'Password') }}</label>
                            <input type="password" class="form-control form-control-sm" v-model="settings.smtp_pass" :placeholder="t('admin_settings_email_password_placeholder', '********')">
                            <div class="form-text">{{ t('admin_settings_email_password_hint', 'Leave masked to keep the existing password.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_encryption', 'Encryption') }}</label>
                            <select class="form-select form-select-sm" v-model="settings.smtp_crypto">
                                <option value="tls">{{ t('admin_settings_email_encryption_tls', 'TLS') }}</option>
                                <option value="ssl">{{ t('admin_settings_email_encryption_ssl', 'SSL') }}</option>
                                <option value="">{{ t('admin_settings_email_encryption_none', 'None') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_from_email', 'From Email') }}</label>
                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_from_name', 'From Name') }}</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                        </div>
                    </div>

                    <div v-if="settings.email_protocol !== 'smtp'" class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_from_email', 'From Email') }}</label>
                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_email_from_name', 'From Name') }}</label>
                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'transfers'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_transfers_heading', 'Transfers') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_transfers_default_expiry', 'Default Expiry (Days)') }}</label>
                            <input type="number" min="1" class="form-control form-control-sm" v-model.number="settings.default_transfer_expiry">
                            <div class="form-text">{{ t('admin_settings_transfers_default_expiry_hint', 'Used when no expiry is provided.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_transfers_max_expiry', 'Max Expiry (Days)') }}</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.transfer_max_expiry_days">
                            <div class="form-text">{{ t('admin_settings_transfers_max_expiry_hint', 'Upper bound for all transfers.') }}</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminTransferNotifyDownload" v-model="settings.transfer_default_notify_download">
                                <label class="form-check-label small fw-bold" for="adminTransferNotifyDownload">{{ t('admin_settings_transfers_notify_download', 'Notify On Download (Default)') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'sharing'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_sharing_heading', 'Share Links') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_sharing_default_expiry', 'Default Expiry (Days)') }}</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_default_expiry_days">
                            <div class="form-text">{{ t('admin_settings_sharing_default_expiry_hint', 'Used when expiry is required but not provided.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_sharing_max_expiry', 'Max Expiry (Days)') }}</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_max_expiry_days">
                            <div class="form-text">{{ t('admin_settings_sharing_max_expiry_hint', 'Upper bound for all share links.') }}</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminShareRequireExpiry" v-model="settings.share_require_expiry">
                                <label class="form-check-label small fw-bold" for="adminShareRequireExpiry">{{ t('admin_settings_sharing_require_expiry', 'Require Expiry') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminShareRequirePassword" v-model="settings.share_require_password">
                                <label class="form-check-label small fw-bold" for="adminShareRequirePassword">{{ t('admin_settings_sharing_require_password', 'Require Password') }}</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">{{ t('admin_settings_sharing_upload_heading', 'Upload-Mode Policy') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" id="adminAllowUploadMode" v-model="settings.allow_public_uploads">
                                <label class="form-check-label small fw-bold" for="adminAllowUploadMode">{{ t('admin_settings_sharing_allow_upload_mode', 'Allow upload-mode shares') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-text">
                                {{ t('admin_settings_sharing_allow_upload_mode_hint', 'Upload-mode shares are folder-only and block downloads.') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_sharing_allowed_types', 'Allowed File Extensions') }}</label>
                            <textarea
                                class="form-control form-control-sm"
                                rows="3"
                                v-model="settings.share_upload_allowed_extensions_text"
                                :placeholder="t('admin_settings_sharing_allowed_types_placeholder', 'pdf\\njpg\\npng')"
                            ></textarea>
                            <div class="form-text">{{ t('admin_settings_sharing_allowed_types_hint', 'Leave empty to allow all types. One extension per line, without dots.') }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ t('admin_settings_sharing_quota_mb', 'Total Quota (MB)') }}</label>
                            <input type="number" min="0" max="1024000" class="form-control form-control-sm" v-model.number="settings.share_upload_quota_mb">
                            <div class="form-text">{{ t('admin_settings_sharing_quota_mb_hint', '0 disables the quota.') }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ t('admin_settings_sharing_max_files', 'Max Files') }}</label>
                            <input type="number" min="0" max="100000" class="form-control form-control-sm" v-model.number="settings.share_upload_max_files">
                            <div class="form-text">{{ t('admin_settings_sharing_max_files_hint', '0 disables the file-count limit.') }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'governance'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_governance_heading', 'Uploads & Quotas') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_governance_max_upload', 'Max Upload Size (MB)') }}</label>
                            <input type="number" min="0" max="10240" class="form-control form-control-sm" v-model.number="settings.upload_max_file_mb">
                            <div class="form-text">{{ t('admin_settings_governance_max_upload_hint', '0 disables the limit. Applies to single and chunked uploads.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_governance_quota', 'Per-User Quota (MB)') }}</label>
                            <input type="number" min="0" max="102400" class="form-control form-control-sm" v-model.number="settings.quota_per_user_mb">
                            <div class="form-text">{{ t('admin_settings_governance_quota_hint', '0 disables the quota. Applies to the user home directory.') }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'mounts'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_mounts_heading', 'External Mounts') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">{{ t('admin_settings_mounts_allowlist', 'Allowlist (one path per line)') }}</label>
                            <textarea class="form-control form-control-sm" rows="4" v-model="settings.mount_root_allowlist_text" :placeholder="t('admin_settings_mounts_allowlist_placeholder', '/srv/data\\n/mnt/storage')"></textarea>
                            <div class="form-text">{{ t('admin_settings_mounts_allowlist_hint', 'Only paths under these roots can be mounted. Leave empty to disable external mounts.') }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'logging'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_logging_heading', 'Audit Logging') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ t('admin_settings_logging_retention', 'Retention Count') }}</label>
                            <input type="number" min="100" max="20000" step="100" class="form-control form-control-sm" v-model.number="settings.log_retention_count">
                            <div class="form-text">{{ t('admin_settings_logging_retention_hint', 'How many recent audit log entries to keep (100-20000).') }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="settingsTab === 'security'">
                    <h6 class="border-bottom pb-2 mb-3">{{ t('admin_settings_security_heading', 'Session Security') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ t('admin_settings_security_idle_timeout', 'Idle Timeout (Minutes)') }}</label>
                            <input type="number" min="0" max="1440" class="form-control form-control-sm" v-model.number="settings.session_idle_timeout_minutes">
                            <div class="form-text">{{ t('admin_settings_security_idle_timeout_hint', '0 disables idle timeout. Applies to API and UI sessions.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <div v-if="settingsDirty" class="text-warning small mb-2 text-start">
                        {{ t('admin_settings_unsaved', 'You have unsaved settings changes.') }}
                    </div>
                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="validateEmailSettings" :disabled="!settings || isValidatingEmail">
                        {{ isValidatingEmail ? t('admin_settings_validating', 'Validating…') : t('admin_settings_validate', 'Validate') }}
                    </button>
                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="testEmail" :disabled="!settings || isTestingEmail">
                        {{ isTestingEmail ? t('admin_settings_sending', 'Sending…') : t('admin_settings_send_test_email', 'Send Test Email') }}
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="!settingsDirty || isSavingSettings">
                        {{ isSavingSettings ? t('admin_settings_saving', 'Saving…') : t('admin_settings_save', 'Save Settings') }}
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
        t(key, fallback = '') {
            const value = i18n.t(key);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        async loadSettings() {
            this.error = null;
            try {
                this.settings = await Api.get('settings');
                if (!this.settings.mount_root_allowlist_text && Array.isArray(this.settings.mount_root_allowlist)) {
                    this.settings.mount_root_allowlist_text = this.settings.mount_root_allowlist.join('\n');
                }
                if (!this.settings.share_upload_allowed_extensions_text && Array.isArray(this.settings.share_upload_allowed_extensions)) {
                    this.settings.share_upload_allowed_extensions_text = this.settings.share_upload_allowed_extensions.join('\n');
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
            if (typeof this.settings.allow_public_uploads !== 'boolean') {
                this.settings.allow_public_uploads = false;
            }
            if (typeof this.settings.share_upload_quota_mb !== 'number' || Number.isNaN(this.settings.share_upload_quota_mb)) {
                this.settings.share_upload_quota_mb = 0;
            }
            if (typeof this.settings.share_upload_max_files !== 'number' || Number.isNaN(this.settings.share_upload_max_files)) {
                this.settings.share_upload_max_files = 0;
            }
            if (typeof this.settings.share_upload_allowed_extensions_text !== 'string') {
                if (Array.isArray(this.settings.share_upload_allowed_extensions)) {
                    this.settings.share_upload_allowed_extensions_text = this.settings.share_upload_allowed_extensions.join('\n');
                } else {
                    this.settings.share_upload_allowed_extensions_text = '';
                }
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
                this.toastSuccess(this.t('admin_settings_saved', 'Settings saved.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_settings_save_failed', 'Failed to save settings.'));
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
                this.setEmailValidation(true, this.t('admin_settings_test_email_sent', 'Test email sent successfully.'));
                this.toastSuccess(this.t('admin_settings_test_email_sent_short', 'Test email sent.'));
            } catch (e) {
                this.setEmailValidation(false, this.t('admin_settings_test_failed_prefix', 'Test failed: ') + e.message);
                this.toastError(this.t('admin_settings_test_email_failed', 'Test email failed.'));
            } finally {
                this.isTestingEmail = false;
            }
        },
        async validateEmailSettings() {
            if (!this.settings) return;
            this.isValidatingEmail = true;
            try {
                const res = await Api.post('settings/validate-email', this.settings);
                this.setEmailValidation(true, res.message || this.t('admin_settings_validation_success', 'Validation successful'));
            } catch (e) {
                this.setEmailValidation(false, this.t('admin_settings_validation_failed_prefix', 'Validation failed: ') + e.message);
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
                title: this.t('admin_settings_send_test_email', 'Send test email'),
                input: 'email',
                inputLabel: this.t('admin_settings_recipient_email', 'Recipient email'),
                inputPlaceholder: this.t('admin_settings_recipient_email_placeholder', 'name@example.com'),
                showCancelButton: true,
                confirmButtonText: this.t('admin_settings_send', 'Send'),
                inputValidator: (value) => {
                    if (!value) return this.t('admin_settings_email_required', 'Email is required');
                    return null;
                }
            });
            if (!result.isConfirmed) return null;
            return result.value;
        }
    }
};
