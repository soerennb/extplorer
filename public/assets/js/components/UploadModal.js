const UploadModal = {
    template: `
    <div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-upload-cloud-2-line me-2"></i> {{ t('upload_files') || 'Upload Files' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Drop Zone -->
                    <div class="p-5 border-2 border-dashed rounded-3 text-center mb-3 bg-light cursor-pointer position-relative"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleDrop"
                         @click="triggerPicker"
                         :class="{'border-primary bg-primary-subtle': dragOver}">
                        
                        <input type="file" ref="fileInput" multiple hidden @change="handleFileSelect">
                        
                        <i class="ri-upload-2-line fs-1 text-muted"></i>
                        <p class="mb-0 text-muted">{{ t('drag_drop_desc') || 'Drag files here or click to browse' }}</p>
                    </div>

                    <!-- File List -->
                    <div v-if="files.length > 0" class="list-group list-group-flush mb-3 upload-list">
                        <div v-for="(f, i) in files" :key="i" class="list-group-item d-flex align-items-center justify-content-between p-2">
                            <div class="d-flex align-items-center text-truncate me-2">
                                <i :class="getFileIcon(f.file.name)" class="me-2 text-secondary"></i>
                                <div>
                                    <div class="text-truncate fw-bold">{{ f.file.name }}</div>
                                    <div class="small text-muted">{{ formatSize(f.file.size) }}</div>
                                </div>
                            </div>
                            
                            <div v-if="f.status === 'pending'">
                                <span v-if="f.valid" class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="ri-check-line"></i> {{ t('ready') || 'Ready' }}
                                </span>
                                <span v-else class="badge bg-danger-subtle text-danger border border-danger-subtle" :title="f.error">
                                    <i class="ri-prohibited-line"></i> {{ t('blocked') || 'Blocked' }}
                                </span>
                                <button class="btn btn-link btn-sm text-secondary ms-2" @click="removeFile(i)"><i class="ri-close-line"></i></button>
                            </div>
                            
                            <div v-else-if="f.status === 'uploading'" class="d-flex align-items-center upload-status">
                                <div class="progress w-100 progress-compact">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" :class="progressClass(f.progress)"></div>
                                </div>
                            </div>
                            
                            <div v-else-if="f.status === 'done'" class="text-success">
                                <i class="ri-checkbox-circle-fill fs-5"></i>
                            </div>
                            
                            <div v-else-if="f.status === 'error'" class="text-danger small text-end">
                                {{ f.errorMessage || 'Error' }}
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="configLoading" class="text-center small text-muted my-2">
                        <div class="spinner-border spinner-border-sm me-1"></div> Checking permissions...
                    </div>

                    <div v-if="hasBlockedFiles" class="alert alert-warning small d-flex align-items-center">
                        <i class="ri-alert-line fs-5 me-2"></i>
                        <div>{{ t('blocked_files_msg') || 'Some files have blocked extensions and will be skipped.' }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ t('close') }}</button>
                    <button type="button" class="btn btn-primary" @click="startUpload" :disabled="uploading || validFilesCount === 0">
                        <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                        {{ t('upload') }} ({{ validFilesCount }})
                    </button>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref, reactive, computed, onMounted } = Vue;
        const dragOver = ref(false);
        const files = ref([]); // { file, valid, error, status: 'pending'|'uploading'|'done'|'error', progress: 0 }
        const fileInput = ref(null);
        const uploading = ref(false);
        const configLoading = ref(false);
        const config = reactive({ allowed: [], blocked: [], system: [] });
        let modalInstance = null;

        const loadConfig = async () => {
            configLoading.value = true;
            try {
                const res = await Api.get('profile/details');
                config.allowed = res.allowed_extensions ? res.allowed_extensions.split(',').map(s => s.trim().toLowerCase()) : [];
                config.blocked = res.blocked_extensions ? res.blocked_extensions.split(',').map(s => s.trim().toLowerCase()) : [];
                config.system = res.system_blocklist || [];
            } catch(e) { console.error(e); }
            finally { configLoading.value = false; }
        };

        const isAllowed = (name) => {
            const ext = name.split('.').pop().toLowerCase();
            if (config.allowed.length > 0) {
                return config.allowed.includes(ext) ? true : 'Not in allowed list';
            }
            if (config.blocked.includes(ext)) return 'In blocked list';
            if (config.system.includes(ext)) return 'System blocked extension';
            return true;
        };

        const addFiles = (fileList) => {
            for (let file of fileList) {
                const check = isAllowed(file.name);
                files.value.push({
                    file: file,
                    valid: check === true,
                    error: check === true ? null : check,
                    status: 'pending',
                    progress: 0
                });
            }
        };

        const handleDrop = (e) => {
            dragOver.value = false;
            if (e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
        };

        const handleFileSelect = (e) => {
            if (e.target.files.length) addFiles(e.target.files);
            e.target.value = ''; // Reset
        };

        const triggerPicker = () => fileInput.value.click();
        
        const removeFile = (index) => files.value.splice(index, 1);

        const progressClass = (value) => {
            const clamped = Math.max(0, Math.min(100, Math.round(value / 5) * 5));
            return `progress-w-${clamped}`;
        };

        const validFilesCount = computed(() => files.value.filter(f => f.valid && f.status === 'pending').length);
        const hasBlockedFiles = computed(() => files.value.some(f => !f.valid));

        const startUpload = async () => {
            if (uploading.value || validFilesCount.value === 0) return;
            uploading.value = true;
            
            const CHUNK_SIZE = 1024 * 1024;
            const pending = files.value.filter(f => f.valid && f.status === 'pending');

            for (let item of pending) {
                item.status = 'uploading';
                try {
                    const file = item.file;
                    const path = store.cwd;
                    
                    if (file.size <= CHUNK_SIZE) {
                        const fd = new FormData(); fd.append('file', file); fd.append('path', path);
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                        
                        const res = await fetch(window.baseUrl + 'api/upload', { method: 'POST', headers, body: fd });
                        if (!res.ok) throw new Error((await res.json()).messages?.error || 'Failed');
                        item.progress = 100;
                    } else {
                        const total = Math.ceil(file.size / CHUNK_SIZE);
                        for (let i = 0; i < total; i++) {
                            const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                            const fd = new FormData(); fd.append('file', chunk); fd.append('filename', file.name);
                            fd.append('chunkIndex', i); fd.append('totalChunks', total); fd.append('path', path);
                            
                            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                            if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                            const res = await fetch(window.baseUrl + 'api/upload_chunk', { method: 'POST', headers, body: fd });
                            if (!res.ok) throw new Error((await res.json()).messages?.error || 'Chunk failed');
                            
                            item.progress = Math.round(((i + 1) / total) * 100);
                        }
                    }
                    item.status = 'done';
                } catch(e) {
                    item.status = 'error';
                    item.errorMessage = e.message;
                }
            }
            
            uploading.value = false;
            if (typeof store.reload === 'function') {
                await store.reload();
            } else {
                await store.loadPath(store.cwd);
                store.refreshTree();
            }
            if (files.value.every(f => f.status === 'done')) {
                setTimeout(() => {
                    modalInstance.hide();
                    files.value = [];
                    Swal.fire(i18n.t('uploaded'), '', 'success');
                }, 500);
            }
        };

        const open = () => {
            files.value = [];
            loadConfig();
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('uploadModal'));
            modalInstance.show();
        };

        const getFileIcon = (name) => {
            const ext = name.split('.').pop().toLowerCase();
            if (['jpg','png','gif'].includes(ext)) return 'ri-image-line';
            if (['pdf'].includes(ext)) return 'ri-file-pdf-line';
            return 'ri-file-line';
        };
        
        const formatSize = (b) => {
            const k=1024, s=['B','KB','MB','GB'];
            const i=Math.floor(Math.log(b)/Math.log(k));
            return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];
        };

        return {
            dragOver, files, fileInput, uploading, configLoading, hasBlockedFiles, validFilesCount,
            handleDrop, handleFileSelect, triggerPicker, removeFile, startUpload, open,
            getFileIcon, formatSize, progressClass, t: (k) => i18n.t(k)
        };
    }
};
