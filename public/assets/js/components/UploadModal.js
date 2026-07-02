const UploadModal = {
    template: `
    <div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-upload-cloud-2-line me-2"></i> {{ t('upload_files', 'Upload Files') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close', 'Close')"></button>
                </div>
                <div class="modal-body">
                    <div class="upload-dropzone p-4 border border-2 border-dashed rounded text-center mb-3 bg-light"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleDrop"
                         :class="{'border-primary bg-primary-subtle': dragOver}">
                        <input id="uploadFileInput" name="upload_files" type="file" ref="fileInput" multiple hidden @change="handleFileSelect" :aria-label="t('upload_browse_files', 'Browse files')">
                        <input id="uploadFolderInput" name="upload_folder_files" type="file" ref="folderInput" multiple webkitdirectory hidden @change="handleFileSelect" :aria-label="t('upload_browse_folder', 'Browse folder')">

                        <i class="ri-upload-2-line fs-1 text-muted"></i>
                        <div class="fw-semibold">{{ dragOver ? t('upload_drop_active', 'Drop files to queue them') : t('drag_drop_desc', 'Drag files here or click to browse') }}</div>
                        <div class="text-muted small mt-1">{{ t('upload_folder_hint', 'Folder uploads keep their folder structure when supported by the browser.') }}</div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="triggerPicker" :disabled="!canUploadHere">
                                <i class="ri-file-add-line me-1" aria-hidden="true"></i>{{ t('upload_browse_files', 'Browse files') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="triggerFolderPicker" :disabled="!canUploadHere">
                                <i class="ri-folder-upload-line me-1" aria-hidden="true"></i>{{ t('upload_browse_folder', 'Browse folder') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="!canUploadHere" class="alert alert-info small d-flex align-items-center">
                        <i class="ri-folder-open-line fs-5 me-2" aria-hidden="true"></i>
                        <div>{{ t('upload_select_target_folder', 'Open a writable folder before uploading files.') }}</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold" for="uploadConflictSelect">{{ t('upload_conflict_label', 'If a file already exists') }}</label>
                            <select id="uploadConflictSelect" class="form-select form-select-sm" v-model="conflictMode" :disabled="uploading">
                                <option value="replace">{{ t('upload_conflict_replace', 'Replace existing file') }}</option>
                                <option value="skip">{{ t('upload_conflict_skip', 'Skip the new file') }}</option>
                                <option value="keep_both">{{ t('upload_conflict_keep_both', 'Keep both files') }}</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="small fw-bold mb-2">{{ t('upload_total_progress', 'Total progress') }}</div>
                            <div class="progress progress-compact" :aria-label="t('upload_total_progress', 'Total progress')">
                                <div class="progress-bar" :class="progressClass(totalProgress)"></div>
                            </div>
                            <div class="small text-muted mt-1">{{ completedCount }} / {{ files.length }} {{ t('upload_files_queued', 'files') }} · {{ totalProgress }}%</div>
                        </div>
                    </div>

                    <div v-if="configLoading" class="text-center small text-muted my-2">
                        <div class="spinner-border spinner-border-sm me-1"></div> {{ t('upload_checking_permissions', 'Checking permissions...') }}
                    </div>

                    <div v-if="hasBlockedFiles" class="alert alert-warning small d-flex align-items-center">
                        <i class="ri-alert-line fs-5 me-2" aria-hidden="true"></i>
                        <div>{{ t('blocked_files_msg', 'Some files have blocked extensions and will be skipped.') }}</div>
                    </div>

                    <div v-if="files.length === 0" class="text-center text-muted py-4 border rounded bg-light-subtle">
                        <i class="ri-inbox-line fs-2 d-block mb-2" aria-hidden="true"></i>
                        {{ t('upload_empty_queue', 'No files queued yet.') }}
                    </div>

                    <div v-else class="list-group list-group-flush mb-3 upload-list">
                        <div v-for="item in files" :key="item.id" class="list-group-item px-0 py-3">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="d-flex align-items-start text-truncate">
                                    <i :class="getFileIcon(item.file.name)" class="me-2 text-secondary fs-5" aria-hidden="true"></i>
                                    <div class="min-w-0">
                                        <div class="text-truncate fw-semibold" :title="item.displayPath">{{ item.file.name }}</div>
                                        <div v-if="item.relativePath" class="small text-muted text-truncate" :title="item.relativePath">{{ item.relativePath }}</div>
                                        <div class="small text-muted">{{ formatSize(item.file.size) }}</div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <span class="badge" :class="statusBadgeClass(item)">{{ statusLabel(item) }}</span>
                                    <button v-if="item.status === 'uploading'" type="button" class="btn btn-sm btn-outline-danger" @click="cancelFile(item)">
                                        <i class="ri-stop-circle-line me-1" aria-hidden="true"></i>{{ t('cancel', 'Cancel') }}
                                    </button>
                                    <button v-if="item.status === 'error' || item.status === 'canceled'" type="button" class="btn btn-sm btn-outline-primary" @click="retryFile(item)" :disabled="uploading">
                                        <i class="ri-restart-line me-1" aria-hidden="true"></i>{{ t('upload_retry', 'Retry') }}
                                    </button>
                                    <button v-if="item.status !== 'uploading'" type="button" class="btn btn-link btn-sm text-secondary" @click="removeFile(item)" :aria-label="t('upload_remove_file', 'Remove file') + ': ' + item.file.name">
                                        <i class="ri-close-line" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="progress progress-compact mt-2" :aria-label="t('upload_file_progress', 'File progress') + ': ' + item.file.name">
                                <div class="progress-bar" :class="progressClass(item.progress)"></div>
                            </div>
                            <div v-if="item.errorMessage" class="text-danger small mt-1">{{ item.errorMessage }}</div>
                            <div v-else-if="item.resultPath && item.resultPath !== item.relativePath" class="text-muted small mt-1">{{ t('upload_saved_as', 'Saved as {path}', { path: item.resultPath }) }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" @click="clearFinished" :disabled="uploading || completedCount === 0">{{ t('upload_clear_finished', 'Clear finished') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ t('close', 'Close') }}</button>
                    <button type="button" class="btn btn-primary" @click="startUpload" :disabled="!canUploadHere || uploading || uploadableCount === 0">
                        <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                        {{ uploading ? t('uploading', 'Uploading...') : t('upload', 'Upload') }} ({{ uploadableCount }})
                    </button>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const { ref, reactive, computed } = Vue;
        const t = (key, fallback = '', params = {}) => {
            const value = i18n.t(key, params);
            return value === key ? (fallback || key) : value;
        };
        const dragOver = ref(false);
        const files = ref([]);
        const fileInput = ref(null);
        const folderInput = ref(null);
        const uploading = ref(false);
        const configLoading = ref(false);
        const conflictMode = ref('replace');
        const config = reactive({ allowed: [], blocked: [], system: [] });
        let modalInstance = null;
        let nextId = 1;

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
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            if (config.allowed.length > 0) {
                return config.allowed.includes(ext) ? true : t('upload_not_allowed', 'Not in allowed list');
            }
            if (config.blocked.includes(ext)) return t('upload_blocked_user', 'In blocked list');
            if (config.system.includes(ext)) return t('upload_blocked_system', 'System blocked extension');
            return true;
        };

        const addFiles = (fileList) => {
            for (const file of Array.from(fileList)) {
                const relativePath = file.webkitRelativePath || '';
                const check = isAllowed(file.name);
                files.value.push({
                    id: nextId++,
                    file,
                    relativePath,
                    displayPath: relativePath || file.name,
                    valid: check === true,
                    error: check === true ? null : check,
                    status: check === true ? 'pending' : 'blocked',
                    progress: 0,
                    uploadedBytes: 0,
                    errorMessage: check === true ? '' : check,
                    resultPath: '',
                    controller: null,
                    xhr: null
                });
            }
        };

        const handleDrop = (e) => {
            dragOver.value = false;
            if (e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
        };

        const handleFileSelect = (e) => {
            if (e.target.files.length) addFiles(e.target.files);
            e.target.value = '';
        };

        const triggerPicker = () => fileInput.value?.click();
        const triggerFolderPicker = () => folderInput.value?.click();
        const removeFile = (item) => {
            if (item.status === 'uploading') return;
            files.value = files.value.filter((f) => f.id !== item.id);
        };
        const cancelFile = (item) => {
            if (item.xhr) item.xhr.abort();
            if (item.controller) item.controller.abort();
            item.status = 'canceled';
            item.errorMessage = t('upload_canceled', 'Canceled');
        };
        const retryFile = (item) => {
            item.status = item.valid ? 'pending' : 'blocked';
            item.progress = 0;
            item.uploadedBytes = 0;
            item.errorMessage = item.valid ? '' : item.error;
            item.resultPath = '';
        };
        const clearFinished = () => {
            files.value = files.value.filter((item) => !['done', 'skipped'].includes(item.status));
        };

        const progressClass = (value) => {
            const clamped = Math.max(0, Math.min(100, Math.round(value / 5) * 5));
            return `progress-w-${clamped}`;
        };

        const uploadableCount = computed(() => files.value.filter(f => f.valid && ['pending', 'error', 'canceled'].includes(f.status)).length);
        const completedCount = computed(() => files.value.filter(f => ['done', 'skipped'].includes(f.status)).length);
        const hasBlockedFiles = computed(() => files.value.some(f => f.status === 'blocked'));
        const canUploadHere = computed(() => Boolean(store.cwd && store.cwd !== '/'));
        const totalBytes = computed(() => files.value.filter(f => f.valid).reduce((sum, item) => sum + item.file.size, 0));
        const totalUploadedBytes = computed(() => files.value.filter(f => f.valid).reduce((sum, item) => {
            if (['done', 'skipped'].includes(item.status)) return sum + item.file.size;
            return sum + Math.round(item.file.size * (item.progress / 100));
        }, 0));
        const totalProgress = computed(() => totalBytes.value > 0 ? Math.round((totalUploadedBytes.value / totalBytes.value) * 100) : 0);

        const updateCsrfFromResponse = (res) => {
            if (Api.refreshCsrfToken) Api.refreshCsrfToken(res);
        };

        const uploadSingle = (item, path) => new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('file', item.file);
            fd.append('path', path);
            fd.append('relativePath', item.relativePath);
            fd.append('conflict', conflictMode.value);

            const xhr = new XMLHttpRequest();
            item.xhr = xhr;
            xhr.open('POST', window.baseUrl + 'api/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (window.csrfHash) xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfHash);
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                item.progress = Math.min(99, Math.round((event.loaded / event.total) * 100));
            };
            xhr.onload = async () => {
                item.xhr = null;
                const headerToken = xhr.getResponseHeader('X-CSRF-HASH');
                if (headerToken) window.csrfHash = headerToken;
                let payload = {};
                try {
                    payload = xhr.responseText ? JSON.parse(xhr.responseText) : {};
                } catch (e) {
                    payload = { message: xhr.responseText };
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(new Error(Api.errorFromPayload(payload, 'Upload failed')));
                    return;
                }
                resolve(payload);
            };
            xhr.onerror = () => {
                item.xhr = null;
                reject(new Error(Api.genericNetworkError()));
            };
            xhr.onabort = () => {
                item.xhr = null;
                reject(new DOMException('Upload canceled', 'AbortError'));
            };
            xhr.send(fd);
        });

        const uploadChunked = async (item, path) => {
            const CHUNK_SIZE = 1024 * 1024;
            const total = Math.ceil(item.file.size / CHUNK_SIZE);
            item.controller = new AbortController();

            for (let i = 0; i < total; i++) {
                const chunk = item.file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                const fd = new FormData();
                fd.append('file', chunk);
                fd.append('filename', item.file.name);
                fd.append('chunkIndex', i);
                fd.append('totalChunks', total);
                fd.append('path', path);
                fd.append('relativePath', item.relativePath);
                fd.append('conflict', conflictMode.value);

                const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                const res = await fetch(window.baseUrl + 'api/upload_chunk', {
                    method: 'POST',
                    headers,
                    body: fd,
                    signal: item.controller.signal
                });
                updateCsrfFromResponse(res);
                const payload = await Api.parseResponseBody(res);
                if (!res.ok) {
                    throw new Error(Api.errorFromPayload(payload, 'Upload chunk failed'));
                }

                item.progress = Math.round(((i + 1) / total) * 100);
                if (i === total - 1) {
                    return payload || {};
                }
            }

            return {};
        };

        const startUpload = async () => {
            if (!canUploadHere.value) {
                Swal.fire(i18n.t('upload_files'), t('upload_select_target_folder', 'Open a writable folder before uploading files.'), 'info');
                return;
            }
            if (uploading.value || uploadableCount.value === 0) return;
            uploading.value = true;
            const path = store.cwd;
            const pending = files.value.filter(f => f.valid && ['pending', 'error', 'canceled'].includes(f.status));

            for (const item of pending) {
                item.status = 'uploading';
                item.progress = 0;
                item.errorMessage = '';
                item.resultPath = '';
                try {
                    const payload = item.file.size <= 1024 * 1024
                        ? await uploadSingle(item, path)
                        : await uploadChunked(item, path);
                    item.progress = 100;
                    item.resultPath = payload.path || item.relativePath || item.file.name;
                    item.status = payload.status === 'skipped' ? 'skipped' : 'done';
                } catch(e) {
                    item.status = e.name === 'AbortError' ? 'canceled' : 'error';
                    item.errorMessage = item.status === 'canceled' ? t('upload_canceled', 'Canceled') : e.message;
                } finally {
                    item.controller = null;
                    item.xhr = null;
                }
            }

            uploading.value = false;
            if (typeof store.reload === 'function') {
                await store.reload();
            } else {
                await store.loadPath(store.cwd);
                store.refreshTree();
            }
            if (files.value.length > 0 && files.value.every(f => ['done', 'skipped', 'blocked'].includes(f.status))) {
                Swal.fire(i18n.t('uploaded'), '', 'success');
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
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'ri-image-line';
            if (['pdf'].includes(ext)) return 'ri-file-pdf-line';
            if (['zip','tar','gz','rar','7z'].includes(ext)) return 'ri-file-zip-line';
            return 'ri-file-line';
        };
        
        const formatSize = (bytes) => {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B','KB','MB','GB'];
            const index = Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(k)));
            return `${parseFloat((bytes / Math.pow(k, index)).toFixed(2))} ${sizes[index]}`;
        };

        const statusLabel = (item) => {
            const labels = {
                pending: t('ready', 'Ready'),
                blocked: t('blocked', 'Blocked'),
                uploading: t('uploading', 'Uploading...'),
                done: t('upload_done', 'Done'),
                skipped: t('upload_skipped', 'Skipped'),
                error: t('error', 'Error'),
                canceled: t('upload_canceled', 'Canceled')
            };
            return labels[item.status] || item.status;
        };

        const statusBadgeClass = (item) => {
            if (item.status === 'done') return 'bg-success-subtle text-success border border-success-subtle';
            if (item.status === 'skipped') return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            if (item.status === 'uploading') return 'bg-primary-subtle text-primary border border-primary-subtle';
            if (item.status === 'error' || item.status === 'blocked') return 'bg-danger-subtle text-danger border border-danger-subtle';
            if (item.status === 'canceled') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            return 'bg-success-subtle text-success border border-success-subtle';
        };

        return {
            dragOver, files, fileInput, folderInput, uploading, configLoading, hasBlockedFiles, canUploadHere, uploadableCount,
            completedCount, totalProgress, conflictMode, handleDrop, handleFileSelect, triggerPicker,
            triggerFolderPicker, removeFile, cancelFile, retryFile, clearFinished, startUpload, open,
            getFileIcon, formatSize, progressClass, statusLabel, statusBadgeClass, t
        };
    }
};
