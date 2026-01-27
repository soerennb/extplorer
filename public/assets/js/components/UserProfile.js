const UserProfile = {
    template: `
    <div
        class="modal fade"
        id="userProfileModal"
        tabindex="-1"
        data-bs-backdrop="static"
        :data-bs-keyboard="forcePasswordChange ? 'false' : 'true'"
        aria-labelledby="userProfileModalTitle"
        aria-modal="true"
        role="dialog"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userProfileModalTitle">
                        <i class="ri-user-settings-line me-2"></i>
                        {{ t('profile_settings') || 'Profile & Settings' }}
                    </h5>
                    <button v-if="!forcePasswordChange" type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
                </div>
                <div class="modal-body">
                    <div v-if="forcePasswordChange" class="alert alert-warning d-flex align-items-start" role="alert">
                        <i class="ri-alert-line fs-5 me-2 mt-1"></i>
                        <div>
                            <strong>{{ t('password_change_required') || 'Password change required' }}</strong>
                            <div class="small">{{ t('password_change_required_desc') || 'You are using the default admin password. Please set a new password now.' }}</div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'general' }"
                                type="button"
                                role="tab"
                                id="profile-tab-general"
                                :aria-selected="activeTab === 'general' ? 'true' : 'false'"
                                aria-controls="profile-panel-general"
                                @click="setTab('general')"
                            >
                                {{ t('general') || 'General' }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'security' }"
                                type="button"
                                role="tab"
                                id="profile-tab-security"
                                :aria-selected="activeTab === 'security' ? 'true' : 'false'"
                                aria-controls="profile-panel-security"
                                @click="setTab('security')"
                            >
                                {{ t('security') || 'Security' }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" v-if="canMount">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'mounts' }"
                                type="button"
                                role="tab"
                                id="profile-tab-mounts"
                                :aria-selected="activeTab === 'mounts' ? 'true' : 'false'"
                                aria-controls="profile-panel-mounts"
                                @click="setTab('mounts')"
                            >
                                {{ t('mounts') || 'Mounts' }}
                            </button>
                        </li>
                    </ul>

                    <div
                        v-show="activeTab === 'general'"
                        role="tabpanel"
                        id="profile-panel-general"
                        aria-labelledby="profile-tab-general"
                        tabindex="0"
                    >
                        <div v-if="loading" class="text-center py-4"><div class="spinner-border" role="status" :aria-label="t('loading') || 'Loading'"></div></div>
                        <div v-else class="row">
                            <label class="col-sm-3 col-form-label fw-bold">{{ t('username') || 'Username' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.username"></div>

                            <label class="col-sm-3 col-form-label fw-bold">{{ t('role') || 'Role' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.role"></div>

                            <label class="col-sm-3 col-form-label fw-bold">{{ t('home_dir') || 'Home Directory' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.home_dir"></div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="mb-2">{{ t('language') || 'Language' }}</h6>
                            <div class="small text-muted mb-2">{{ t('language_desc') || 'Choose the interface language for this browser.' }}</div>
                            <div class="row g-2 align-items-center">
                                <label class="col-sm-3 col-form-label fw-bold" for="profile-language-select">{{ t('language') || 'Language' }}</label>
                                <div class="col-sm-9">
                                    <select
                                        id="profile-language-select"
                                        class="form-select form-select-sm"
                                        v-model="selectedLocale"
                                        @change="updateLocale"
                                    >
                                        <option v-for="loc in availableLocales" :key="loc.code" :value="loc.code">
                                            {{ t(loc.labelKey) || loc.labelFallback }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="mb-3">{{ t('file_extensions') || 'File Extensions' }}</h6>

                            <div v-if="details.allowed_extensions" class="mb-2">
                                <span class="small fw-bold d-block text-success">{{ t('allowed') || 'Allowed' }}:</span>
                                <span v-for="ext in details.allowed_extensions.split(',')" :key="ext" class="badge bg-success-subtle text-success border border-success-subtle me-1">{{ ext.trim() }}</span>
                            </div>

                            <div v-if="details.blocked_extensions" class="mb-2">
                                <span class="small fw-bold d-block text-danger">{{ t('blocked_user') || 'Blocked (User)' }}:</span>
                                <span v-for="ext in details.blocked_extensions.split(',')" :key="ext" class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">{{ ext.trim() }}</span>
                            </div>

                            <div v-if="details.system_blocklist && details.system_blocklist.length">
                                <span class="small fw-bold d-block text-secondary">{{ t('blocked_system') || 'Blocked (System)' }}:</span>
                                <span v-for="ext in details.system_blocklist" :key="ext" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">{{ ext }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-show="activeTab === 'security'"
                        role="tabpanel"
                        id="profile-panel-security"
                        aria-labelledby="profile-tab-security"
                        tabindex="0"
                    >
                        <h6 class="border-bottom pb-2 mb-3">{{ t('change_password') || 'Change Password' }}</h6>

                        <div v-if="passwordMessage.text" class="alert" :class="passwordMessage.type === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                            {{ passwordMessage.text }}
                        </div>

                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label" for="profile-current-password">{{ t('current_password') || 'Current Password' }}</label>
                            <div class="col-sm-9">
                                <input
                                    id="profile-current-password"
                                    type="password"
                                    class="form-control"
                                    v-model="passwordForm.current"
                                    :placeholder="t('password_placeholder') || '••••••••'"
                                    autocomplete="current-password"
                                >
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label" for="profile-new-password">{{ t('new_password') || 'New Password' }}</label>
                            <div class="col-sm-9">
                                <input
                                    id="profile-new-password"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': passwordTouched && passwordInvalid }"
                                    v-model="passwordForm.new"
                                    :placeholder="t('password_min_hint') || 'Min 8 chars'"
                                    autocomplete="new-password"
                                >

                                <div class="profile-strength mt-2" aria-live="polite">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress profile-strength-bar flex-grow-1" role="progressbar" :aria-valuenow="passwordStrength.score" aria-valuemin="0" aria-valuemax="4">
                                            <div class="progress-bar" :class="passwordStrength.widthClass + ' ' + passwordStrength.colorClass"></div>
                                        </div>
                                        <span class="small fw-semibold" :class="passwordStrength.textClass">{{ passwordStrength.label }}</span>
                                    </div>
                                </div>

                                <div v-if="passwordTouched && passwordInvalid" class="invalid-feedback d-block">
                                    {{ passwordInvalidMessage }}
                                </div>

                                <ul class="profile-password-hints mt-2 mb-0">
                                    <li :class="passwordChecks.length ? 'text-success' : 'text-muted'">{{ t('password_rule_length') || 'At least 8 characters' }}</li>
                                    <li :class="passwordChecks.case ? 'text-success' : 'text-muted'">{{ t('password_rule_case') || 'Uppercase and lowercase letters' }}</li>
                                    <li :class="passwordChecks.number ? 'text-success' : 'text-muted'">{{ t('password_rule_number') || 'At least one number' }}</li>
                                    <li :class="passwordChecks.symbol ? 'text-success' : 'text-muted'">{{ t('password_rule_symbol') || 'At least one symbol' }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label" for="profile-confirm-password">{{ t('confirm_new_password') || 'Confirm New Password' }}</label>
                            <div class="col-sm-9">
                                <input
                                    id="profile-confirm-password"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': passwordTouched && passwordMismatch }"
                                    v-model="passwordForm.confirm"
                                    :placeholder="t('password_confirm_hint') || 'Re-enter new password'"
                                    autocomplete="new-password"
                                >
                                <div v-if="passwordTouched && passwordMismatch" class="invalid-feedback d-block">
                                    {{ t('password_mismatch') || 'Passwords do not match' }}
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 row">
                            <div class="col-sm-9 offset-sm-3">
                                <button class="btn btn-primary btn-sm" @click="updatePassword" :disabled="passwordSaving || passwordInvalid">
                                    <span v-if="passwordSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    {{ passwordSaving ? (t('updating') || 'Updating...') : (t('update') || 'Update') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2 mb-3 mt-4">{{ t('two_factor_auth') || 'Two-Factor Authentication' }}</h6>

                        <div v-if="twoFaStepperVisible" class="profile-stepper mb-3" role="list">
                            <div class="profile-step" role="listitem" :class="twoFaStepClass(1)">
                                <span class="profile-step-index">1</span>
                                <span class="profile-step-label">{{ t('twofa_step_scan') || 'Scan' }}</span>
                            </div>
                            <div class="profile-step" role="listitem" :class="twoFaStepClass(2)">
                                <span class="profile-step-index">2</span>
                                <span class="profile-step-label">{{ t('twofa_step_verify') || 'Verify' }}</span>
                            </div>
                            <div class="profile-step" role="listitem" :class="twoFaStepClass(3)">
                                <span class="profile-step-index">3</span>
                                <span class="profile-step-label">{{ t('twofa_step_recovery') || 'Recovery Codes' }}</span>
                            </div>
                        </div>

                        <div v-if="twoFaMessage.text" class="alert" :class="twoFaMessage.type === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                            {{ twoFaMessage.text }}
                        </div>

                        <div v-if="details['2fa_enabled']" class="alert alert-success d-flex flex-column gap-2" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="ri-shield-check-line fs-4 me-3"></i>
                                <div>
                                    <strong>{{ t('2fa_active') || '2FA is currently enabled.' }}</strong><br>
                                    <span class="small text-muted">{{ t('2fa_enabled_note') || 'Your account is secured with Time-based One-Time Password.' }}</span>
                                </div>
                                <button class="btn btn-outline-danger btn-sm ms-auto" @click="openDisable2fa" :disabled="disable2faState.loading">
                                    <span v-if="disable2faState.loading" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                    {{ t('disable') || 'Disable' }}
                                </button>
                            </div>

                            <div v-if="disable2faState.open" class="card bg-body-tertiary">
                                <div class="card-body">
                                    <h6 class="mb-2">{{ t('disable_2fa_title') || 'Disable two-factor authentication' }}</h6>
                                    <p class="small text-muted mb-3">{{ t('disable_2fa_desc') || 'Re-authenticate with your password or a current authenticator code to continue.' }}</p>

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small" for="disable-2fa-password">{{ t('current_password') || 'Current Password' }}</label>
                                            <input
                                                id="disable-2fa-password"
                                                type="password"
                                                class="form-control form-control-sm"
                                                v-model="disable2faState.password"
                                                autocomplete="current-password"
                                            >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small" for="disable-2fa-code">{{ t('authenticator_code') || 'Authenticator Code' }}</label>
                                            <input
                                                id="disable-2fa-code"
                                                type="text"
                                                inputmode="numeric"
                                                class="form-control form-control-sm"
                                                v-model="disable2faState.code"
                                                :placeholder="t('authenticator_code_hint') || '123456'"
                                                autocomplete="one-time-code"
                                            >
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-link btn-sm text-muted" @click="closeDisable2fa" :disabled="disable2faState.loading">{{ t('cancel') || 'Cancel' }}</button>
                                        <button type="button" class="btn btn-danger btn-sm" @click="submitDisable2fa" :disabled="disable2faState.loading || !canSubmitDisable2fa">
                                            <span v-if="disable2faState.loading" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                            {{ t('disable_2fa_confirm') || 'Disable 2FA' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <p class="small text-muted">{{ t('2fa_desc') || 'Protect your account by requiring a code from your mobile device when logging in.' }}</p>
                            <button v-if="!setup.step" class="btn btn-primary btn-sm" @click="start2faSetup" :disabled="twoFaLoading">
                                <span v-if="twoFaLoading" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                {{ t('enable_2fa') || 'Enable 2FA' }}
                            </button>

                            <div v-if="setup.step === 1" class="card bg-body-tertiary mt-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">{{ t('scan_qr') || 'Scan QR Code' }}</h6>
                                    <p class="small text-muted mb-3">{{ t('scan_qr_hint') || 'Use your authenticator app to scan the QR code, then enter the 6-digit code.' }}</p>
                                    <div class="profile-qr-wrap mb-3">
                                        <img :src="setup.qr" class="qr-image" :alt="t('scan_qr_alt') || 'Two-factor authentication QR code'">
                                    </div>
                                    <div class="profile-secret-block mb-2">
                                        <div class="small text-muted">{{ t('manual_entry_code') || 'Manual entry code' }}</div>
                                        <div class="font-monospace user-select-all">{{ setup.secret }}</div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" @click="copySecret" :disabled="twoFaLoading">
                                            {{ t('copy_code') || 'Copy code' }}
                                        </button>
                                    </div>
                                    <div class="input-group input-group-sm mb-3 qr-input-group">
                                        <input
                                            type="text"
                                            class="form-control"
                                            :placeholder="t('authenticator_code_hint') || '123456'"
                                            v-model="setup.code"
                                            inputmode="numeric"
                                            autocomplete="one-time-code"
                                        >
                                        <button class="btn btn-success" @click="finish2faSetup" :disabled="twoFaLoading || !setup.code">
                                            <span v-if="twoFaLoading" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                            {{ t('verify') || 'Verify' }}
                                        </button>
                                    </div>
                                    <button class="btn btn-link btn-sm text-muted" @click="cancel2faSetup" :disabled="twoFaLoading">{{ t('cancel') || 'Cancel' }}</button>
                                </div>
                            </div>
                        </div>

                        <div v-if="setup.recoveryCodes.length" class="mt-3">
                            <div class="alert alert-warning" role="alert">
                                <h6>{{ t('recovery_codes') || 'Recovery Codes' }}</h6>
                                <p class="small mb-2">{{ t('recovery_codes_desc') || 'Save these codes in a safe place. You can use them if you lose access to your device.' }}</p>
                                <p class="small text-muted mb-2">{{ t('recovery_codes_once') || 'These codes will only be shown once.' }}</p>
                                <div class="profile-recovery-list font-monospace small user-select-all mb-3" aria-live="polite">
                                    <div v-for="code in setup.recoveryCodes" :key="code">{{ code }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="copyRecoveryCodes">{{ t('copy_recovery_codes') || 'Copy codes' }}</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="downloadRecoveryCodes">{{ t('download_recovery_codes') || 'Download codes' }}</button>
                                    <button type="button" class="btn btn-primary btn-sm" @click="completeRecoveryCodes">{{ t('done') || 'Done' }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-show="activeTab === 'mounts'"
                        role="tabpanel"
                        id="profile-panel-mounts"
                        aria-labelledby="profile-tab-mounts"
                        tabindex="0"
                    >
                        <h6 class="border-bottom pb-2 mb-3">{{ t('manage_mounts') || 'Manage Virtual Mounts' }}</h6>
                        <p class="small text-muted">{{ t('mounts_desc') || 'Mount external directories as folders in your root view.' }}</p>

                        <div v-if="mountMessage.text" class="alert" :class="mountMessage.type === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
                            {{ mountMessage.text }}
                        </div>

                        <div v-if="mountsLoading" class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status" :aria-label="t('loading') || 'Loading'"></div></div>
                        <div v-else>
                            <div v-if="mounts.length === 0" class="text-center py-4 text-muted small">
                                {{ t('no_mounts') || 'No mounts defined.' }}
                            </div>
                            <div v-else class="list-group list-group-flush mb-4 border rounded">
                                <div v-for="mount in mounts" :key="mount.id" class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="me-3">
                                        <div class="fw-bold d-flex align-items-center gap-2">
                                            {{ mount.name }}
                                            <span class="badge text-bg-light border">{{ mountTypeLabel(mount.type) }}</span>
                                        </div>
                                        <div class="small text-muted font-monospace">{{ mountSummary(mount) }}</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm" @click="startEditMount(mount)" :disabled="mountSaving || mountsLoading">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" @click="removeMount(mount.id)" :disabled="mountRemovingId === mount.id">
                                            <span v-if="mountRemovingId === mount.id" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                            <i v-else class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-body-tertiary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">{{ mountMode === 'edit' ? (t('edit_mount') || 'Edit Mount') : (t('add_mount') || 'Add New Mount') }}</h6>
                                        <button v-if="mountMode === 'edit'" type="button" class="btn btn-link btn-sm text-muted" @click="cancelEditMount" :disabled="mountSaving">{{ t('cancel_edit') || 'Cancel edit' }}</button>
                                    </div>

                                    <div v-if="mountTestResult.text" class="alert py-2" :class="mountTestResult.type === 'success' ? 'alert-success' : 'alert-danger'" role="status" aria-live="polite">
                                        {{ mountTestResult.text }}
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small" for="mount-name">{{ t('mount_name') || 'Name (Folder Name)' }}</label>
                                            <input
                                                id="mount-name"
                                                type="text"
                                                class="form-control form-control-sm"
                                                :class="mountErrors.name ? 'is-invalid' : ''"
                                                v-model="mountForm.name"
                                                :placeholder="t('mount_name_placeholder') || 'e.g. Projects'"
                                            >
                                            <div v-if="mountErrors.name" class="invalid-feedback d-block">{{ mountErrors.name }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small" for="mount-type">{{ t('mount_type') || 'Type' }}</label>
                                            <select id="mount-type" class="form-select form-select-sm" v-model="mountForm.type" :disabled="mountSaving">
                                                <option value="local">{{ t('mount_type_local') || 'Local' }}</option>
                                                <option value="ftp">{{ t('mount_type_ftp') || 'FTP' }}</option>
                                                <option value="sftp">{{ t('mount_type_sftp') || 'SFTP' }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4" v-if="mountForm.type === 'local'">
                                            <label class="form-label small" for="mount-path">{{ t('mount_path') || 'Path (Local Server Path)' }}</label>
                                            <input
                                                id="mount-path"
                                                type="text"
                                                class="form-control form-control-sm"
                                                :class="mountErrors.path ? 'is-invalid' : ''"
                                                v-model="mountForm.config.path"
                                                :placeholder="t('mount_path_placeholder') || '/absolute/path/on/server'"
                                            >
                                            <div v-if="mountErrors.path" class="invalid-feedback d-block">{{ mountErrors.path }}</div>
                                        </div>
                                    </div>

                                    <div v-if="mountForm.type !== 'local'" class="mt-2">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small" for="mount-host">{{ t('mount_host') || 'Host' }}</label>
                                                <input
                                                    id="mount-host"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    :class="mountErrors.host ? 'is-invalid' : ''"
                                                    v-model="mountForm.config.host"
                                                    :placeholder="t('mount_host_placeholder') || 'example.com'"
                                                >
                                                <div v-if="mountErrors.host" class="invalid-feedback d-block">{{ mountErrors.host }}</div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small" for="mount-port">{{ t('mount_port') || 'Port' }}</label>
                                                <input
                                                    id="mount-port"
                                                    type="number"
                                                    class="form-control form-control-sm"
                                                    v-model.number="mountForm.config.port"
                                                    min="1"
                                                    max="65535"
                                                >
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small" for="mount-root">{{ t('mount_root') || 'Root Path' }}</label>
                                                <input
                                                    id="mount-root"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    v-model="mountForm.config.root"
                                                    :placeholder="t('mount_root_placeholder') || '/'"
                                                >
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-6">
                                                <label class="form-label small" for="mount-user">{{ t('mount_user') || 'Username' }}</label>
                                                <input
                                                    id="mount-user"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    :class="mountErrors.user ? 'is-invalid' : ''"
                                                    v-model="mountForm.config.user"
                                                    :placeholder="t('mount_user_placeholder') || 'user'"
                                                >
                                                <div v-if="mountErrors.user" class="invalid-feedback d-block">{{ mountErrors.user }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small" for="mount-pass">{{ t('mount_pass') || 'Password' }}</label>
                                                <input
                                                    id="mount-pass"
                                                    type="password"
                                                    class="form-control form-control-sm"
                                                    :class="mountErrors.pass ? 'is-invalid' : ''"
                                                    v-model="mountForm.config.pass"
                                                    :placeholder="mountForm.hasStoredPass ? (t('mount_pass_keep') || 'Leave blank to keep current password') : (t('password_placeholder') || '••••••••')"
                                                    autocomplete="new-password"
                                                >
                                                <div v-if="mountErrors.pass" class="invalid-feedback d-block">{{ mountErrors.pass }}</div>
                                                <div v-else-if="mountForm.hasStoredPass" class="form-text small">{{ t('mount_pass_keep_hint') || 'Leave the password empty to keep the existing one.' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 d-flex flex-wrap justify-content-end gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="testMount" :disabled="mountTesting || mountSaving || !canTestMount">
                                            <span v-if="mountTesting" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                            {{ t('test_connection') || 'Test connection' }}
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" @click="saveMount" :disabled="mountSaving || !canSubmitMount">
                                            <span v-if="mountSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                            {{ mountMode === 'edit' ? (t('mount_save') || 'Save Mount') : (t('mount_add') || 'Add Mount') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref, reactive, computed, onMounted, watch, nextTick } = Vue;
        const details = ref({});
        const loading = ref(false);
        const activeTab = ref('general');
        const passwordForm = reactive({ current: '', new: '', confirm: '' });
        const passwordSaving = ref(false);
        const passwordMessage = reactive({ type: '', text: '' });
        const twoFaMessage = reactive({ type: '', text: '' });
        const twoFaLoading = ref(false);
        const availableLocales = ref([
            { code: 'en', labelKey: 'language_english', labelFallback: 'English' },
            { code: 'de', labelKey: 'language_german', labelFallback: 'Deutsch' },
            { code: 'fr', labelKey: 'language_french', labelFallback: 'Français' }
        ]);
        const selectedLocale = ref(i18n.locale || (typeof i18n.preferredLocale === 'function' ? i18n.preferredLocale() : 'en'));

        const setup = reactive({ step: 0, qr: '', secret: '', code: '', recoveryCodes: [] });
        const disable2faState = reactive({ open: false, password: '', code: '', loading: false });

        const mounts = ref([]);
        const mountsLoading = ref(false);
        const mountSaving = ref(false);
        const mountTesting = ref(false);
        const mountRemovingId = ref('');
        const mountMode = ref('create');
        const editingMountId = ref('');
        const mountMessage = reactive({ type: '', text: '' });
        const mountTestResult = reactive({ type: '', text: '' });
        const mountErrors = reactive({ name: '', path: '', host: '', user: '', pass: '' });

        const mountForm = reactive({
            id: '',
            name: '',
            type: 'local',
            hasStoredPass: false,
            config: { path: '', host: '', port: 21, user: '', pass: '', root: '/' },
        });

        const forcePasswordChange = ref(!!window.forcePasswordChange);
        const profileTabKey = 'extplorer_profile_tab';
        let modalInstance = null;
        let modalEl = null;
        let lastActiveElement = null;

        const canMount = computed(() => {
            const perms = window.userPermissions || [];
            return perms.includes('*') || perms.includes('mount_external');
        });

        const validTabs = computed(() => (canMount.value ? ['general', 'security', 'mounts'] : ['general', 'security']));
        const normalizeTab = (tab) => (validTabs.value.includes(tab) ? tab : 'general');
        const readStoredTab = () => {
            try {
                const stored = localStorage.getItem(profileTabKey);
                if (stored) {
                    return normalizeTab(stored);
                }
            } catch (e) {
                // Ignore storage failures
            }
            return 'general';
        };
        const persistTab = (tab) => {
            try {
                localStorage.setItem(profileTabKey, tab);
            } catch (e) {
                // Ignore storage failures
            }
        };

        const passwordChecks = computed(() => {
            const value = passwordForm.new || '';
            return {
                length: value.length >= 8,
                case: /[a-z]/.test(value) && /[A-Z]/.test(value),
                number: /\d/.test(value),
                symbol: /[^A-Za-z0-9]/.test(value),
            };
        });

        const passwordScore = computed(() => {
            const checks = passwordChecks.value;
            let score = 0;
            if (checks.length) score += 1;
            if (checks.case) score += 1;
            if (checks.number) score += 1;
            if (checks.symbol) score += 1;
            return score;
        });

        const passwordStrength = computed(() => {
            const score = passwordScore.value;
            const labelMap = {
                0: t('password_strength_0') || 'Too weak',
                1: t('password_strength_1') || 'Weak',
                2: t('password_strength_2') || 'Fair',
                3: t('password_strength_3') || 'Good',
                4: t('password_strength_4') || 'Strong',
            };
            const widthClass = `strength-score-${score}`;
            const colorClass = score >= 4 ? 'bg-success' : score >= 3 ? 'bg-info' : score >= 2 ? 'bg-warning' : 'bg-danger';
            const textClass = score >= 4 ? 'text-success' : score >= 3 ? 'text-info' : score >= 2 ? 'text-warning' : 'text-danger';
            return { score, label: labelMap[score], widthClass, colorClass, textClass };
        });

        const passwordTouched = computed(() => passwordForm.new.length > 0 || passwordForm.confirm.length > 0);
        const passwordMismatch = computed(() => passwordForm.new !== '' && passwordForm.confirm !== '' && passwordForm.new !== passwordForm.confirm);
        const passwordInvalidMessage = computed(() => {
            if (!passwordChecks.value.length) return t('password_rule_length') || 'At least 8 characters';
            if (passwordMismatch.value) return t('password_mismatch') || 'Passwords do not match';
            return t('password_requirements_not_met') || 'Password requirements are not met';
        });

        const passwordInvalid = computed(() => {
            if (!passwordForm.current) return true;
            if (!passwordChecks.value.length) return true;
            if (passwordMismatch.value) return true;
            return false;
        });

        const twoFaStepperVisible = computed(() => setup.step > 0 || setup.recoveryCodes.length > 0);
        const twoFaStepClass = (step) => {
            let current = 0;
            if (setup.recoveryCodes.length > 0) {
                current = 3;
            } else if (setup.step === 1) {
                current = setup.code.trim() ? 2 : 1;
            }
            if (step < current) return 'is-complete';
            if (step === current) return 'is-active';
            return '';
        };

        const canSubmitDisable2fa = computed(() => {
            return disable2faState.password.trim() !== '' || disable2faState.code.trim() !== '';
        });

        const clearMountErrors = () => {
            mountErrors.name = '';
            mountErrors.path = '';
            mountErrors.host = '';
            mountErrors.user = '';
            mountErrors.pass = '';
        };

        const resetMountMessages = () => {
            mountMessage.type = '';
            mountMessage.text = '';
            mountTestResult.type = '';
            mountTestResult.text = '';
        };

        const resetMountForm = () => {
            mountForm.id = '';
            mountForm.name = '';
            mountForm.type = 'local';
            mountForm.hasStoredPass = false;
            mountForm.config.path = '';
            mountForm.config.host = '';
            mountForm.config.port = 21;
            mountForm.config.user = '';
            mountForm.config.pass = '';
            mountForm.config.root = '/';
        };

        const setMountMessage = (type, text) => {
            mountMessage.type = type;
            mountMessage.text = text;
        };

        const setTwoFaMessage = (type, text) => {
            twoFaMessage.type = type;
            twoFaMessage.text = text;
        };

        const setPasswordMessage = (type, text) => {
            passwordMessage.type = type;
            passwordMessage.text = text;
        };

        const loadDetails = async () => {
            loading.value = true;
            try {
                details.value = await Api.get('profile/details');
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const loadMounts = async () => {
            mountsLoading.value = true;
            try {
                mounts.value = await Api.get('mounts');
            } catch (e) {
                console.error(e);
                setMountMessage('error', e.message || (t('mount_load_failed') || 'Failed to load mounts'));
            } finally {
                mountsLoading.value = false;
            }
        };

        const setTab = async (tab, options = {}) => {
            const { persist = true } = options;
            const nextTab = normalizeTab(tab);
            activeTab.value = nextTab;
            if (persist && !forcePasswordChange.value) {
                persistTab(nextTab);
            }
            if (nextTab === 'mounts') {
                resetMountMessages();
                await loadMounts();
            }
        };

        const validateMountForm = () => {
            clearMountErrors();
            let valid = true;

            if (!mountForm.name.trim()) {
                mountErrors.name = t('mount_error_name') || 'Name is required.';
                valid = false;
            }

            if (mountForm.type === 'local') {
                if (!mountForm.config.path.trim()) {
                    mountErrors.path = t('mount_error_path') || 'Local path is required.';
                    valid = false;
                }
            } else {
                if (!mountForm.config.host.trim()) {
                    mountErrors.host = t('mount_error_host') || 'Host is required.';
                    valid = false;
                }
                if (!mountForm.config.user.trim()) {
                    mountErrors.user = t('mount_error_user') || 'Username is required.';
                    valid = false;
                }
                if (!mountForm.config.pass.trim() && !mountForm.hasStoredPass) {
                    mountErrors.pass = t('mount_error_pass') || 'Password is required.';
                    valid = false;
                }
            }

            return valid;
        };

        const canSubmitMount = computed(() => {
            if (!mountForm.name.trim()) return false;
            if (mountForm.type === 'local') return !!mountForm.config.path.trim();
            if (!mountForm.config.host.trim() || !mountForm.config.user.trim()) return false;
            if (!mountForm.config.pass.trim() && !mountForm.hasStoredPass) return false;
            return true;
        });

        const canTestMount = computed(() => {
            if (!mountForm.name.trim()) return false;
            if (mountForm.type === 'local') return !!mountForm.config.path.trim();
            if (!mountForm.config.host.trim() || !mountForm.config.user.trim()) return false;
            if (!mountForm.config.pass.trim() && !mountForm.hasStoredPass) return false;
            return true;
        });

        const startEditMount = async (mount) => {
            resetMountMessages();
            mountMode.value = 'edit';
            editingMountId.value = mount.id;

            try {
                const fullMount = await Api.get(`mounts/${mount.id}`);
                mountForm.id = fullMount.id;
                mountForm.name = fullMount.name || '';
                mountForm.type = fullMount.type || 'local';
                mountForm.hasStoredPass = !!fullMount.has_pass;

                mountForm.config.path = fullMount.config?.path || '';
                mountForm.config.host = fullMount.config?.host || '';
                mountForm.config.port = fullMount.config?.port || (mountForm.type === 'sftp' ? 22 : 21);
                mountForm.config.user = fullMount.config?.user || '';
                mountForm.config.pass = '';
                mountForm.config.root = fullMount.config?.root || '/';
            } catch (e) {
                console.error(e);
                setMountMessage('error', e.message || (t('mount_edit_failed') || 'Failed to load mount details'));
            }
        };

        const cancelEditMount = () => {
            mountMode.value = 'create';
            editingMountId.value = '';
            resetMountForm();
            clearMountErrors();
            mountTestResult.type = '';
            mountTestResult.text = '';
        };

        const saveMount = async () => {
            resetMountMessages();
            if (!validateMountForm()) return;

            mountSaving.value = true;
            try {
                const payload = {
                    name: mountForm.name,
                    type: mountForm.type,
                    config: { ...mountForm.config },
                };

                if (mountMode.value === 'edit' && editingMountId.value) {
                    await Api.put(`mounts/${editingMountId.value}`, payload);
                    setMountMessage('success', t('mount_updated') || 'Mount updated successfully.');
                } else {
                    await Api.post('mounts', payload);
                    setMountMessage('success', t('mount_added') || 'Mount added successfully.');
                }

                cancelEditMount();
                await loadMounts();
                store.reload();
            } catch (e) {
                setMountMessage('error', e.message || (t('mount_save_failed') || 'Failed to save mount'));
            } finally {
                mountSaving.value = false;
            }
        };

        const testMount = async () => {
            resetMountMessages();
            if (!validateMountForm()) return;

            mountTesting.value = true;
            try {
                const payload = {
                    id: mountMode.value === 'edit' ? editingMountId.value : null,
                    name: mountForm.name,
                    type: mountForm.type,
                    config: { ...mountForm.config },
                };
                const res = await Api.post('mounts/test', payload);
                mountTestResult.type = 'success';
                mountTestResult.text = t('mount_test_success') || 'Connection successful.';

                if (res?.config?.path && mountForm.type === 'local') {
                    mountForm.config.path = res.config.path;
                }
            } catch (e) {
                mountTestResult.type = 'error';
                mountTestResult.text = e.message || (t('mount_test_failed') || 'Connection failed.');
            } finally {
                mountTesting.value = false;
            }
        };

        const removeMount = async (id) => {
            const confirmed = await Swal.fire({
                title: t('confirm_title') || 'Are you sure?',
                text: t('mount_remove_confirm') || 'This mount will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: t('remove') || 'Remove',
            });
            if (!confirmed.isConfirmed) return;

            mountRemovingId.value = id;
            try {
                await Api.delete('mounts/' + id);
                if (editingMountId.value === id) {
                    cancelEditMount();
                }
                await loadMounts();
                store.reload();
            } catch (e) {
                setMountMessage('error', e.message || (t('mount_remove_failed') || 'Failed to remove mount'));
            } finally {
                mountRemovingId.value = '';
            }
        };

        const updatePassword = async () => {
            setPasswordMessage('', '');
            if (passwordInvalid.value) return;

            passwordSaving.value = true;
            try {
                await Api.put('profile/password', {
                    password: passwordForm.new,
                    old_password: passwordForm.current,
                });
                setPasswordMessage('success', t('password_updated') || 'Password updated.');
                passwordForm.current = '';
                passwordForm.new = '';
                passwordForm.confirm = '';
                forcePasswordChange.value = false;
                window.forcePasswordChange = false;
            } catch (e) {
                setPasswordMessage('error', e.message || (t('password_update_failed') || 'Failed to update password'));
            } finally {
                passwordSaving.value = false;
            }
        };

        const start2faSetup = async () => {
            setTwoFaMessage('', '');
            twoFaLoading.value = true;
            try {
                const res = await Api.get('profile/2fa/setup');
                setup.qr = res.qr;
                setup.secret = res.secret;
                setup.step = 1;
                setup.code = '';
            } catch (e) {
                setTwoFaMessage('error', e.message || (t('twofa_setup_failed') || 'Failed to start 2FA setup'));
            } finally {
                twoFaLoading.value = false;
            }
        };

        const cancel2faSetup = () => {
            setup.step = 0;
            setup.code = '';
            setup.qr = '';
            setup.secret = '';
        };

        const finish2faSetup = async () => {
            setTwoFaMessage('', '');
            if (!setup.code) return;

            twoFaLoading.value = true;
            try {
                const res = await Api.post('profile/2fa/enable', {
                    secret: setup.secret,
                    code: setup.code,
                });
                details.value['2fa_enabled'] = true;
                setup.step = 2;
                setup.recoveryCodes = res.recovery_codes || [];
                setup.code = '';
            } catch (e) {
                setTwoFaMessage('error', e.message || (t('twofa_verify_failed') || 'Invalid verification code'));
            } finally {
                twoFaLoading.value = false;
            }
        };

        const openDisable2fa = () => {
            disable2faState.open = true;
            disable2faState.password = '';
            disable2faState.code = '';
            setTwoFaMessage('', '');
        };

        const closeDisable2fa = () => {
            disable2faState.open = false;
            disable2faState.password = '';
            disable2faState.code = '';
        };

        const submitDisable2fa = async () => {
            setTwoFaMessage('', '');
            if (!canSubmitDisable2fa.value) return;

            disable2faState.loading = true;
            try {
                await Api.post('profile/2fa/disable', {
                    password: disable2faState.password,
                    code: disable2faState.code,
                });
                details.value['2fa_enabled'] = false;
                closeDisable2fa();
                setTwoFaMessage('success', t('twofa_disabled') || 'Two-factor authentication disabled.');
            } catch (e) {
                setTwoFaMessage('error', e.message || (t('twofa_disable_failed') || 'Failed to disable 2FA'));
            } finally {
                disable2faState.loading = false;
            }
        };

        const copyText = async (text, successMessage) => {
            try {
                await navigator.clipboard.writeText(text);
                Swal.fire({ icon: 'success', title: successMessage, timer: 1400, showConfirmButton: false });
            } catch (e) {
                Swal.fire(i18n.t('error') || 'Error', e.message || (t('copy_failed') || 'Copy failed'), 'error');
            }
        };

        const copySecret = () => copyText(setup.secret, t('copy_code_success') || 'Code copied');

        const copyRecoveryCodes = () => copyText(setup.recoveryCodes.join('\n'), t('copy_recovery_codes_success') || 'Recovery codes copied');

        const downloadRecoveryCodes = () => {
            const content = setup.recoveryCodes.join('\n');
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'extplorer-recovery-codes.txt';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        };

        const completeRecoveryCodes = () => {
            setup.recoveryCodes = [];
            setup.step = 0;
        };

        const mountSummary = (mount) => {
            if (mount.type === 'local') return mount.config?.path || '';
            const host = mount.config?.host || '';
            const port = mount.config?.port || '';
            const root = mount.config?.root || '/';
            return `${(mount.type || 'remote').toUpperCase()} ${host}:${port}${root ? ' ' + root : ''}`;
        };

        const mountTypeLabel = (type) => {
            if (type === 'ftp') return 'FTP';
            if (type === 'sftp') return 'SFTP';
            return t('mount_type_local') || 'Local';
        };

        const updateLocale = async () => {
            const nextLocale = selectedLocale.value || 'en';
            await i18n.setLocale(nextLocale);
            selectedLocale.value = i18n.locale;
            Swal.fire({
                icon: 'success',
                title: t('language_updated') || 'Language updated',
                timer: 1200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        };

        watch(() => i18n.locale, (val) => {
            if (val && selectedLocale.value !== val) {
                selectedLocale.value = val;
            }
        });

        watch(canMount, (allowed) => {
            if (!allowed && activeTab.value === 'mounts') {
                setTab('general', { persist: false });
            }
        });

        watch(() => mountForm.type, (val, prev) => {
            resetMountMessages();
            clearMountErrors();
            if (val === 'ftp' && (!mountForm.config.port || mountForm.config.port === 22)) mountForm.config.port = 21;
            if (val === 'sftp' && (!mountForm.config.port || mountForm.config.port === 21)) mountForm.config.port = 22;
            if (val === 'local') {
                mountForm.hasStoredPass = false;
                mountForm.config.pass = '';
            } else if (prev === 'local' && mountMode.value === 'edit') {
                mountForm.hasStoredPass = false;
            }
        });

        const open = async (tab = null) => {
            lastActiveElement = document.activeElement;
            await loadDetails();
            if (forcePasswordChange.value) {
                await setTab('security', { persist: false });
            } else if (tab) {
                await setTab(tab);
            } else {
                await setTab(readStoredTab(), { persist: false });
            }
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: !forcePasswordChange.value,
                });
            }
            modalInstance.show();
            await nextTick();
            const panelId = activeTab.value === 'general' ? 'profile-panel-general' : activeTab.value === 'security' ? 'profile-panel-security' : 'profile-panel-mounts';
            const panel = document.getElementById(panelId);
            panel?.focus();
        };

        onMounted(() => {
            modalEl = document.getElementById('userProfileModal');
            if (!modalEl) return;

            modalEl.addEventListener('hide.bs.modal', (event) => {
                if (forcePasswordChange.value) {
                    event.preventDefault();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                    lastActiveElement.focus();
                }
            });
        });

        return {
            activeTab,
            availableLocales,
            selectedLocale,
            updateLocale,
            details,
            loading,
            passwordForm,
            passwordSaving,
            passwordMessage,
            passwordChecks,
            passwordStrength,
            passwordTouched,
            passwordMismatch,
            passwordInvalid,
            passwordInvalidMessage,
            setup,
            twoFaLoading,
            twoFaMessage,
            twoFaStepperVisible,
            twoFaStepClass,
            disable2faState,
            canSubmitDisable2fa,
            canMount,
            mounts,
            mountsLoading,
            mountForm,
            mountMode,
            mountSaving,
            mountTesting,
            mountRemovingId,
            mountMessage,
            mountTestResult,
            mountErrors,
            canTestMount,
            setTab,
            loadMounts,
            saveMount,
            testMount,
            removeMount,
            startEditMount,
            cancelEditMount,
            updatePassword,
            start2faSetup,
            cancel2faSetup,
            finish2faSetup,
            openDisable2fa,
            closeDisable2fa,
            submitDisable2fa,
            copySecret,
            copyRecoveryCodes,
            downloadRecoveryCodes,
            completeRecoveryCodes,
            mountSummary,
            mountTypeLabel,
            open,
            forcePasswordChange,
            t,
        };
    },
};

function t(key) {
    return i18n.t(key);
}
