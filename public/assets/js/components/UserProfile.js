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
                            <a class="nav-link" :class="{active: activeTab === 'security'}" href="#" @click.prevent="activeTab = 'Security'">{{ t('security') || 'Security' }}</a>
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
                        
                        <div v-if="details.2fa_enabled" class="alert alert-success d-flex align-items-center">
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
                                        <img :src="setup.qr" style="width: 200px; height: 200px;">
                                    </div>
                                    <p class="small user-select-all font-monospace">{{ setup.secret }}</p>
                                    <div class="input-group input-group-sm mb-3" style="max-width: 300px; margin: 0 auto;">
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
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref, reactive, onMounted } = Vue;
        const details = ref({});
        const loading = ref(false);
        const activeTab = ref('general');
        const passwordForm = reactive({ new: '' });
        const setup = reactive({ step: 0, qr: '', secret: '', code: '', recoveryCodes: [] });
        let modalInstance = null;

        const loadDetails = async () => {
            loading.value = true;
            try {
                details.value = await Api.get('profile/details');
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const updatePassword = async () => {
            try {
                await Api.post('profile/password', { password: passwordForm.new });
                Swal.fire('Success', 'Password updated', 'success');
                passwordForm.new = '';
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
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

        const open = () => {
            loadDetails();
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('userProfileModal'));
            modalInstance.show();
        };

        // Expose to parent via ref
        return { 
            activeTab, details, loading, passwordForm, setup,
            updatePassword, start2faSetup, finish2faSetup, disable2fa,
            open, t: (k) => i18n.t(k)
        };
    }
};
