const FileHistoryModal = {
    template: `
    <div class="modal fade" id="fileHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-history-line me-2"></i> {{ t('version_history') || 'Version History' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="loading" class="text-center py-4"><div class="spinner-border"></div></div>
                    <div v-else-if="versions.length === 0" class="text-center py-4 text-muted">
                        <i class="ri-information-line fs-2 d-block mb-2"></i>
                        {{ t('no_versions') || 'No previous versions found for this file.' }}
                    </div>
                    <div v-else>
                        <p class="small text-muted mb-3">{{ t('history_desc') || 'Restoring a version will overwrite the current file.' }}</p>
                        <table class="table table-sm table-hover small">
                            <thead>
                                <tr>
                                    <th>{{ t('date') }}</th>
                                    <th>{{ t('size') }}</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="v in versions" :key="v.id">
                                    <td>{{ v.date }}</td>
                                    <td>{{ formatSize(v.size) }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-success btn-sm py-0" @click="restore(v.id)">
                                            {{ t('restore') || 'Restore' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref } = Vue;
        const versions = ref([]);
        const loading = ref(false);
        const targetPath = ref('');
        let modalInstance = null;

        const loadVersions = async (path) => {
            loading.value = true;
            try {
                const res = await Api.get('versions/list', { path });
                versions.value = res.versions;
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const open = (file) => {
            targetPath.value = file.path;
            loadVersions(file.path);
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('fileHistoryModal'));
            modalInstance.show();
        };

        const restore = async (versionId) => {
            const res = await Swal.fire({
                title: i18n.t('confirm_restore') || 'Restore this version?',
                text: i18n.t('restore_text') || 'The current file content will be replaced.',
                icon: 'warning',
                showCancelButton: true
            });

            if (res.isConfirmed) {
                loading.value = true;
                try {
                    await Api.post('versions/restore', { path: targetPath.value, version_id: versionId });
                    modalInstance.hide();
                    Swal.fire(i18n.t('restored'), '', 'success');
                    store.loadPath(store.cwd); // Refresh
                } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
                finally { loading.value = false; }
            }
        };

        const formatSize = (b) => {
            const k=1024, s=['B','KB','MB','GB'];
            const i=Math.floor(Math.log(b)/Math.log(k));
            return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];
        };

        return { open, versions, loading, restore, formatSize, t: (k) => i18n.t(k) };
    }
};
