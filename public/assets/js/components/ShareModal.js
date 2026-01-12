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
                            <button class="btn btn-outline-primary" @click="copyLink"><i class="ri-file-copy-line"></i></button>
                        </div>
                        
                        <div class="alert alert-light border small">
                            <strong>{{ t('created') }}:</strong> {{ formatDate(currentShare.created_at) }}<br>
                            <span v-if="currentShare.expires_at"><strong>{{ t('expires') }}:</strong> {{ formatDate(currentShare.expires_at) }}<br></span>
                            <span v-if="currentShare.password_hash"><strong>{{ t('password_protected') || 'Password Protected' }}</strong><br></span>
                            <strong>{{ t('downloads') || 'Downloads' }}:</strong> {{ currentShare.downloads }}
                        </div>

                        <button class="btn btn-danger w-100" @click="deleteShare">{{ t('stop_sharing') || 'Stop Sharing' }}</button>
                    </div>

                    <div v-else>
                        <p class="small text-muted">{{ t('share_desc') || 'Create a public link for this item.' }}</p>
                        
                        <div class="mb-3">
                            <label class="form-label">{{ t('password_optional') || 'Password (Optional)' }}</label>
                            <input type="password" class="form-control" v-model="form.password" placeholder="***">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ t('expiration_optional') || 'Expiration (Optional)' }}</label>
                            <select class="form-select" v-model="form.expiryDays">
                                <option :value="0">{{ t('never') || 'Never' }}</option>
                                <option :value="1">1 {{ t('day') || 'Day' }}</option>
                                <option :value="7">7 {{ t('days') || 'Days' }}</option>
                                <option :value="30">30 {{ t('days') || 'Days' }}</option>
                            </select>
                        </div>

                        <button class="btn btn-primary w-100" @click="createShare">{{ t('create_link') || 'Create Link' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref, reactive, computed } = Vue;
        const loading = ref(false);
        const currentShare = ref(null);
        const targetPath = ref('');
        const form = reactive({ password: '', expiryDays: 0 });
        let modalInstance = null;

        const shareUrl = computed(() => {
            if (!currentShare.value) return '';
            return window.baseUrl + 's/' + currentShare.value.hash;
        });

        const formatDate = (ts) => new Date(ts * 1000).toLocaleString();

        const open = async (file) => {
            targetPath.value = file.path;
            form.password = '';
            form.expiryDays = 0;
            currentShare.value = null;
            
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('shareModal'));
            modalInstance.show();
            
            // Check if already shared
            loading.value = true;
            try {
                const res = await Api.get('share/list');
                const existing = res.items.find(s => s.path === file.path);
                if (existing) currentShare.value = existing;
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const createShare = async () => {
            loading.value = true;
            try {
                let expires = null;
                if (form.expiryDays > 0) {
                    expires = Math.floor(Date.now() / 1000) + (form.expiryDays * 86400);
                }

                const res = await Api.post('share/create', {
                    path: targetPath.value,
                    password: form.password,
                    expires: expires
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
            i.select();
            document.execCommand('copy');
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        };

        return {
            open, loading, currentShare, form, createShare, deleteShare, shareUrl, copyLink, formatDate,
            t: (k) => i18n.t(k)
        };
    }
};
