const UserProfile = {
    template: `
    <div class="modal fade" id="userProfileModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-user-settings-line me-2"></i> {{ t('profile_settings') || 'Profile & Settings' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: activeTab === 'general'}" href="#" @click.prevent="activeTab = 'general'">{{ t('general') || 'General' }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: activeTab === 'security'}" href="#" @click.prevent="activeTab = 'security'">{{ t('security') || 'Security' }}</a>
                        </li>
                        <li class="nav-item" v-if="canMount">
                            <a class="nav-link" :class="{active: activeTab === 'mounts'}" href="#" @click.prevent="loadMounts">{{ t('mounts') || 'Mounts' }}</a>
                        </li>
                    </ul>

                    <div v-if="activeTab === 'general'">
                        <div v-if="loading" class="text-center py-4"><div class="spinner-border"></div></div>
                        <div v-else class="row">
                            <label class="col-sm-3 col-form-label fw-bold">{{ t('username') || 'Username' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.username"></div>
                            
                            <label class="col-sm-3 col-form-label fw-bold">{{ t('role') || 'Role' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.role"></div>
                            
                            <label class="col-sm-3 col-form-label fw-bold">{{ t('home_dir') || 'Home Directory' }}</label>
                            <div class="col-sm-9"><input type="text" readonly class="form-control-plaintext" :value="details.home_dir"></div>
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

                    <div v-if="activeTab === 'security'">
                        <!-- Change Password -->
                        <h6 class="border-bottom pb-2 mb-3">{{ t('change_password') || 'Change Password' }}</h6>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">{{ t('new_password') || 'New Password' }}</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" v-model="passwordForm.new" placeholder="Min 8 chars">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-9 offset-sm-3">
                                <button class="btn btn-primary btn-sm" @click="updatePassword" :disabled="!passwordForm.new || passwordForm.new.length < 8">{{ t('update') || 'Update' }}</button>
                            </div>
                        </div>

                        <!-- 2FA -->
                        <h6 class="border-bottom pb-2 mb-3 mt-4">{{ t('two_factor_auth') || 'Two-Factor Authentication' }}</h6>
                        
                        <div v-if="details['2fa_enabled']" class="alert alert-success d-flex align-items-center">
                            <i class="ri-shield-check-line fs-4 me-3"></i>
                            <div>
                                <strong>{{ t('2fa_active') || '2FA is currently enabled.' }}</strong><br>
                                <span class="small text-muted">Your account is secured with Time-based One-Time Password.</span>
                            </div>
                            <button class="btn btn-outline-danger btn-sm ms-auto" @click="disable2fa">{{ t('disable') || 'Disable' }}</button>
                        </div>
                        
                        <div v-else>
                            <p class="small text-muted">{{ t('2fa_desc') || 'Protect your account by requiring a code from your mobile device when logging in.' }}</p>
                            <button v-if="!setup.step" class="btn btn-primary btn-sm" @click="start2faSetup">{{ t('enable_2fa') || 'Enable 2FA' }}</button>
                            
                            <!-- Setup Wizard -->
                            <div v-if="setup.step === 1" class="card bg-body-tertiary">
                                <div class="card-body text-center">
                                    <h6 class="card-title">{{ t('scan_qr') || 'Scan QR Code' }}</h6>
                                    <div class="my-3 bg-white p-2 d-inline-block rounded">
                                        <img :src="setup.qr" class="qr-image">
                                    </div>
                                    <p class="small user-select-all font-monospace">{{ setup.secret }}</p>
                                    <div class="input-group input-group-sm mb-3 qr-input-group">
                                        <input type="text" class="form-control" placeholder="000 000" v-model="setup.code">
                                        <button class="btn btn-success" @click="finish2faSetup" :disabled="!setup.code">{{ t('verify') || 'Verify' }}</button>
                                    </div>
                                    <button class="btn btn-link btn-sm text-muted" @click="setup.step = 0">{{ t('cancel') || 'Cancel' }}</button>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Codes Modal inside Modal -->
                        <div v-if="setup.recoveryCodes.length" class="mt-3">
                            <div class="alert alert-warning">
                                <h6>{{ t('recovery_codes') || 'Recovery Codes' }}</h6>
                                <p class="small mb-2">Save these codes in a safe place. You can use them if you lose access to your device.</p>
                                <div class="bg-white p-2 border rounded font-monospace small user-select-all">
                                    <div v-for="code in setup.recoveryCodes" :key="code">{{ code }}</div>
                                </div>
                                <button class="btn btn-primary btn-sm mt-2" @click="setup.recoveryCodes = []">{{ t('done') || 'Done' }}</button>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'mounts'">
                        <h6 class="border-bottom pb-2 mb-3">{{ t('manage_mounts') || 'Manage Virtual Mounts' }}</h6>
                        <p class="small text-muted">{{ t('mounts_desc') || 'Mount external directories as folders in your root view.' }}</p>
                        
                        <div v-if="mountsLoading" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>
                        <div v-else>
                            <div v-if="mounts.length === 0" class="text-center py-4 text-muted small">
                                {{ t('no_mounts') || 'No mounts defined.' }}
                            </div>
                            <div v-else class="list-group list-group-flush mb-4 border rounded">
                                <div v-for="mount in mounts" :key="mount.id" class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">{{ mount.name }}</div>
                                        <div class="small text-muted font-monospace">{{ mountSummary(mount) }}</div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" @click="removeMount(mount.id)">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card bg-body-tertiary">
                                <div class="card-body">
                                    <h6>{{ t('add_mount') || 'Add New Mount' }}</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">{{ t('mount_name') || 'Name (Folder Name)' }}</label>
                                            <input type="text" class="form-control form-control-sm" v-model="newMount.name" placeholder="e.g. Projects">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">{{ t('mount_type') || 'Type' }}</label>
                                            <select class="form-select form-select-sm" v-model="newMount.type">
                                                <option value="local">{{ t('mount_type_local') || 'Local' }}</option>
                                                <option value="ftp">{{ t('mount_type_ftp') || 'FTP' }}</option>
                                                <option value="sftp">{{ t('mount_type_sftp') || 'SFTP' }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4" v-if="newMount.type === 'local'">
                                            <label class="form-label small">{{ t('mount_path') || 'Path (Local Server Path)' }}</label>
                                            <input type="text" class="form-control form-control-sm" v-model="newMount.config.path" placeholder="/absolute/path/on/server">
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1" v-if="newMount.type !== 'local'">
                                        <div class="col-md-6">
                                            <label class="form-label small">{{ t('mount_host') || 'Host' }}</label>
                                            <input type="text" class="form-control form-control-sm" v-model="newMount.config.host" placeholder="example.com">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">{{ t('mount_port') || 'Port' }}</label>
                                            <input type="number" class="form-control form-control-sm" v-model.number="newMount.config.port" min="1" max="65535">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">{{ t('mount_root') || 'Root Path' }}</label>
                                            <input type="text" class="form-control form-control-sm" v-model="newMount.config.root" placeholder="/">
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1" v-if="newMount.type !== 'local'">
                                        <div class="col-md-6">
                                            <label class="form-label small">{{ t('mount_user') || 'Username' }}</label>
                                            <input type="text" class="form-control form-control-sm" v-model="newMount.config.user" placeholder="user">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">{{ t('mount_pass') || 'Password' }}</label>
                                            <input type="password" class="form-control form-control-sm" v-model="newMount.config.pass" placeholder="••••••••">
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <button class="btn btn-primary btn-sm" @click="addMount" :disabled="!canSubmitMount">{{ t('mount_add') || 'Add Mount' }}</button>
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
        const { ref, reactive, computed, onMounted, watch } = Vue;
        const details = ref({});
        const loading = ref(false);
        const activeTab = ref('general');
        const passwordForm = reactive({ new: '' });
        const setup = reactive({ step: 0, qr: '', secret: '', code: '', recoveryCodes: [] });
        const mounts = ref([]);
        const mountsLoading = ref(false);
        const newMount = reactive({
            name: '',
            type: 'local',
            config: { path: '', host: '', port: 21, user: '', pass: '', root: '/' }
        });
        let modalInstance = null;

        const canMount = computed(() => {
            const perms = window.userPermissions || [];
            return perms.includes('*') || perms.includes('mount_external');
        });

        const loadDetails = async () => {
            loading.value = true;
            try {
                details.value = await Api.get('profile/details');
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const loadMounts = async () => {
            activeTab.value = 'mounts';
            mountsLoading.value = true;
            try {
                mounts.value = await Api.get('mounts');
            } catch(e) { console.error(e); }
            finally { mountsLoading.value = false; }
        };

        const addMount = async () => {
            try {
                await Api.post('mounts', newMount);
                resetNewMount();
                await loadMounts();
                Swal.fire(i18n.t('success'), i18n.t('mount_added'), 'success');
                store.reload();
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const removeMount = async (id) => {
            if (!confirm(i18n.t('confirm_title'))) return;
            try {
                await Api.delete('mounts/' + id);
                await loadMounts();
                store.reload();
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const updatePassword = async () => {
            try {
                await Api.post('profile/password', { password: passwordForm.new });
                Swal.fire('Success', 'Password updated', 'success');
                passwordForm.new = '';
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        };

        const resetNewMount = () => {
            newMount.name = '';
            newMount.type = 'local';
            newMount.config.path = '';
            newMount.config.host = '';
            newMount.config.port = 21;
            newMount.config.user = '';
            newMount.config.pass = '';
            newMount.config.root = '/';
        };

        const canSubmitMount = computed(() => {
            if (!newMount.name) return false;
            if (newMount.type === 'local') {
                return !!newMount.config.path;
            }
            return !!newMount.config.host && !!newMount.config.user && !!newMount.config.pass;
        });

        const mountSummary = (mount) => {
            if (mount.type === 'local') return mount.config?.path || '';
            const host = mount.config?.host || '';
            const port = mount.config?.port || '';
            const root = mount.config?.root || '/';
            return `${(mount.type || 'remote').toUpperCase()} ${host}:${port}${root ? ' ' + root : ''}`;
        };

        const start2faSetup = async () => {
            try {
                const res = await Api.get('profile/2fa/setup');
                setup.qr = res.qr;
                setup.secret = res.secret;
                setup.step = 1;
                setup.code = '';
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        };

        const finish2faSetup = async () => {
            try {
                const res = await Api.post('profile/2fa/enable', { secret: setup.secret, code: setup.code });
                details.value['2fa_enabled'] = true;
                setup.step = 0;
                setup.recoveryCodes = res.recovery_codes;
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        };

        const disable2fa = async () => {
            const c = await Swal.fire({ title: 'Disable 2FA?', icon: 'warning', showCancelButton: true });
            if (c.isConfirmed) {
                try {
                    await Api.post('profile/2fa/disable', {});
                    details.value['2fa_enabled'] = false;
                } catch(e) { Swal.fire('Error', e.message, 'error'); }
            }
        };

        watch(() => newMount.type, (val) => {
            if (val === 'ftp' && (!newMount.config.port || newMount.config.port === 22)) newMount.config.port = 21;
            if (val === 'sftp' && (!newMount.config.port || newMount.config.port === 21)) newMount.config.port = 22;
        });

        const open = () => {
            loadDetails();
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('userProfileModal'));
            modalInstance.show();
        };

        // Expose to parent via ref
        return { 
            activeTab, details, loading, passwordForm, setup,
            canMount, mounts, mountsLoading, newMount, loadMounts, addMount, removeMount,
            updatePassword, start2faSetup, finish2faSetup, disable2fa,
            canSubmitMount, mountSummary,
            open, t: (k) => i18n.t(k)
        };
    }
};
