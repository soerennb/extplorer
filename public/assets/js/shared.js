function createSharedApp(config) {
    const { createApp, ref, reactive, computed, onMounted } = Vue;
    const { hash, baseUrl, csrfTokenName, csrfHash, isFile, share, locale, translations, uploadMaxFileMb, uploadMode, uploadPolicy, shareTitle, shareModeLabel, shareExpiresAt, shareExpiresDate, shareSender, shareCreatedBy, filename, size } = config;
    window.routeBaseUrl = baseUrl;
    window.csrfTokenName = csrfTokenName;
    window.csrfHash = csrfHash;
    window.i18n = { t: (key, fallback) => translations[key] || fallback || key };
    const t = (key, fallback = '', params = null) => {
        let value = translations[key];
        if (typeof value !== 'string' || value.length === 0) {
            value = fallback || key;
        }
        if (params && typeof params === 'object') {
            for (const [paramKey, paramValue] of Object.entries(params)) {
                value = value.replaceAll(`{${paramKey}}`, String(paramValue));
            }
        }
        return value;
    };

    createApp({
        render: sharedAppTemplateRender,
        setup() {
            const files = ref([]);
            const loading = ref(false);
            const currentPath = ref('');
            const searchQuery = ref('');
            const sortKey = ref('type_name');
            const previewState = reactive({ src: '', type: '', filename: '' });
            const fileInput = ref(null);
            const dropzoneActive = ref(false);
            const dragDepth = ref(0);
            const uploading = ref(false);
            const uploadMessage = ref('');
            const uploadError = ref('');
            const uploadPolicyState = ref(uploadPolicy && typeof uploadPolicy === 'object' ? uploadPolicy : {});
            const uploadQueue = ref([]);
            const uploadProcessing = ref(false);
            let previewModal = null;

            const loadPath = async (path = '') => {
                loading.value = true;
                try {
                    const res = await Api.read('s/' + hash + '/ls', { path });
                    files.value = Array.isArray(res.items) ? res.items : [];
                    if (res?.upload_policy && typeof res.upload_policy === 'object') {
                        uploadPolicyState.value = res.upload_policy;
                    }
                    currentPath.value = path;
                    searchQuery.value = '';
                } catch(e) { console.error(e); }
                finally { loading.value = false; }
            };

            const resetUploadState = () => {
                uploadMessage.value = '';
                uploadError.value = '';
            };

            const toNumber = (value) => {
                const n = Number(value);
                return Number.isFinite(n) ? n : 0;
            };

            const policyMaxFileMb = computed(() => {
                const fromPolicy = toNumber(uploadPolicyState.value?.max_file_mb);
                if (fromPolicy > 0) return fromPolicy;
                return toNumber(uploadMaxFileMb);
            });

            const policyMaxFileBytes = computed(() => {
                const bytes = toNumber(uploadPolicyState.value?.max_file_bytes);
                if (bytes > 0) return bytes;
                const mb = policyMaxFileMb.value;
                return mb > 0 ? mb * 1024 * 1024 : 0;
            });

            const allowedExtensions = computed(() => {
                const raw = uploadPolicyState.value?.allowed_extensions;
                return Array.isArray(raw) ? raw.map((ext) => String(ext).toLowerCase()) : [];
            });

            const hasAllowedExtensions = computed(() => allowedExtensions.value.length > 0);

            const allowedExtensionsLabel = computed(() => {
                const label = uploadPolicyState.value?.allowed_extensions_label;
                if (typeof label === 'string' && label.trim() !== '') {
                    return label;
                }
                return allowedExtensions.value.map((ext) => `.${ext}`).join(', ');
            });

            const quotaBytes = computed(() => toNumber(uploadPolicyState.value?.quota_bytes));
            const quotaUsedBytes = computed(() => toNumber(uploadPolicyState.value?.quota_used_bytes));
            const hasQuotaLimit = computed(() => quotaBytes.value > 0);
            const quotaRemainingBytes = computed(() => {
                if (!hasQuotaLimit.value) return 0;
                return Math.max(0, quotaBytes.value - quotaUsedBytes.value);
            });

            const maxFiles = computed(() => toNumber(uploadPolicyState.value?.max_files));
            const filesUsed = computed(() => toNumber(uploadPolicyState.value?.files_used));
            const hasFileCountLimit = computed(() => maxFiles.value > 0);
            const filesRemaining = computed(() => {
                if (!hasFileCountLimit.value) return 0;
                return Math.max(0, maxFiles.value - filesUsed.value);
            });

            const openFilePicker = () => {
                if (!fileInput.value) return;
                fileInput.value.value = '';
                fileInput.value.click();
            };

            const onFileInputChange = (event) => {
                const input = event?.target;
                if (!input?.files?.length) return;
                handleFiles(input.files);
            };

            const onDragEnter = () => {
                if (!uploadMode) return;
                dragDepth.value += 1;
                dropzoneActive.value = true;
            };

            const onDragOver = () => {
                if (!uploadMode) return;
                dropzoneActive.value = true;
            };

            const onDragLeave = () => {
                if (!uploadMode) return;
                dragDepth.value = Math.max(0, dragDepth.value - 1);
                if (dragDepth.value === 0) {
                    dropzoneActive.value = false;
                }
            };

            const onDrop = (event) => {
                if (!uploadMode) return;
                dragDepth.value = 0;
                dropzoneActive.value = false;
                const dt = event?.dataTransfer;
                if (!dt?.files?.length) return;
                handleFiles(dt.files);
            };

            const getExtFromName = (name) => (String(name).split('.').pop() || '').toLowerCase();

            const isExtensionAllowed = (file) => {
                if (!hasAllowedExtensions.value) return true;
                const ext = getExtFromName(file?.name || '');
                return ext !== '' && allowedExtensions.value.includes(ext);
            };

            const progressWidthClass = (progress) => {
                const rounded = Math.min(100, Math.max(0, Math.round(toNumber(progress) / 5) * 5));
                return `progress-w-${rounded}`;
            };

            const queueStatusLabel = (item) => {
                if (item.status === 'pending_scan') return t('upload_pending_scan', 'Waiting for virus scan');
                switch (item.status) {
                    case 'uploading':
                        return t('shared_status_uploading', 'Uploading');
                    case 'done':
                        return t('shared_status_done', 'Done');
                    case 'error':
                        return t('shared_status_error', 'Error');
                    case 'skipped':
                        return t('shared_status_skipped', 'Skipped');
                    case 'queued':
                    default:
                        return t('shared_status_queued', 'Queued');
                }
            };

            const queueStatusBadgeClass = (item) => {
                if (item.status === 'pending_scan') return 'bg-warning-subtle text-warning-emphasis';
                switch (item.status) {
                    case 'uploading':
                        return 'bg-primary';
                    case 'done':
                        return 'bg-success';
                    case 'error':
                        return 'bg-danger';
                    case 'skipped':
                        return 'bg-warning text-dark';
                    case 'queued':
                    default:
                        return 'bg-secondary';
                }
            };

            const queueProgressBarClass = (item) => {
                const classes = [];
                if (item.status === 'uploading') {
                    classes.push('bg-primary', 'progress-bar-striped', 'progress-bar-animated');
                } else if (item.status === 'done') {
                    classes.push('bg-success');
                } else if (item.status === 'error') {
                    classes.push('bg-danger');
                } else if (item.status === 'skipped') {
                    classes.push('bg-warning');
                } else {
                    classes.push('bg-secondary');
                }
                classes.push(progressWidthClass(item.progress));
                return classes;
            };

            const createQueueItem = (file, index) => {
                const now = Date.now();
                return {
                    id: `${now}_${index}_${file.name}`,
                    file,
                    name: file.name,
                    size: toNumber(file.size),
                    status: 'queued',
                    progress: 0,
                    error: '',
                };
            };

            const buildQueueItems = (filesArray) => {
                let provisionalBytes = quotaUsedBytes.value;
                let provisionalFiles = filesUsed.value;
                const items = [];

                filesArray.forEach((file, index) => {
                    const item = createQueueItem(file, index);
                    const sizeBytes = toNumber(file.size);

                    if (policyMaxFileBytes.value > 0 && sizeBytes > policyMaxFileBytes.value) {
                        item.status = 'skipped';
                        item.error = t(
                            'shared_upload_error_too_large',
                            'Some files exceed the maximum size of {max} MB.',
                            { max: policyMaxFileMb.value }
                        );
                        items.push(item);
                        return;
                    }

                    if (!isExtensionAllowed(file)) {
                        item.status = 'skipped';
                        item.error = hasAllowedExtensions.value
                            ? t('shared_upload_error_type', 'File type not allowed. Allowed types: {types}.', { types: allowedExtensionsLabel.value })
                            : t('shared_upload_error_type_generic', 'File type not allowed.');
                        items.push(item);
                        return;
                    }

                    if (hasQuotaLimit.value && (provisionalBytes + sizeBytes) > quotaBytes.value) {
                        item.status = 'skipped';
                        item.error = t('shared_upload_error_quota', 'Upload would exceed the remaining quota.');
                        items.push(item);
                        return;
                    }

                    if (hasFileCountLimit.value && (provisionalFiles + 1) > maxFiles.value) {
                        item.status = 'skipped';
                        item.error = t('shared_upload_error_max_files', 'Upload would exceed the maximum number of files.');
                        items.push(item);
                        return;
                    }

                    provisionalBytes += sizeBytes;
                    provisionalFiles += 1;
                    items.push(item);
                });

                return items;
            };

            const uploadItem = async (item) => {
                item.status = 'uploading';
                item.error = '';
                item.progress = 0;
                const form = new FormData();
                form.append('file', item.file);
                form.append('path', currentPath.value || '');
                try {
                    const json = await Api.upload('s/' + hash + '/upload', form, {
                        onProgress: (fraction) => { item.progress = Math.round(fraction * 100); }
                    });
                    item.status = json.status === 'pending_scan' ? 'pending_scan' : 'done';
                    item.progress = 100;
                    if (json?.upload_policy) uploadPolicyState.value = json.upload_policy;
                    return { ok: true, json };
                } catch (error) {
                    item.status = 'error';
                    item.error = error.message;
                    return { ok: false, message: error.message };
                }
            };

            const processQueue = async () => {
                if (!uploadMode || uploadProcessing.value) return;
                uploadProcessing.value = true;
                uploading.value = true;

                let successes = 0;
                let failures = 0;

                try {
                    // Keep draining the queue, including items added mid-flight.
                    // eslint-disable-next-line no-constant-condition
                    while (true) {
                        const nextItem = uploadQueue.value.find((item) => item.status === 'queued');
                        if (!nextItem) break;
                        // eslint-disable-next-line no-await-in-loop
                        const result = await uploadItem(nextItem);
                        if (result.ok) successes += 1;
                        else failures += 1;
                    }

                    if (successes > 0) {
                        uploadMessage.value = t('shared_upload_success_count', 'Uploaded {count} files.', { count: successes });
                    }
                    if (failures > 0) {
                        uploadError.value = t('shared_upload_error_partial', 'Some uploads failed.');
                    }
                    await loadPath(currentPath.value);
                } catch (e) {
                    console.error(e);
                    uploadError.value = e?.message || t('shared_upload_error', 'Upload failed.');
                } finally {
                    uploading.value = false;
                    uploadProcessing.value = false;
                    dropzoneActive.value = false;
                    dragDepth.value = 0;
                }
            };

            const handleFiles = async (fileList) => {
                if (!uploadMode || !fileList?.length) return;
                resetUploadState();

                const filesArray = Array.from(fileList);
                const newItems = buildQueueItems(filesArray);
                uploadQueue.value = uploadQueue.value.concat(newItems);

                const hasQueued = newItems.some((item) => item.status === 'queued');
                if (!hasQueued) {
                    uploadError.value = t('shared_upload_error_none_queued', 'No files could be queued for upload.');
                    return;
                }

                await processQueue();
            };

            const activeQueueItems = computed(() => uploadQueue.value.filter((item) => item.status !== 'skipped'));

            const overallProgress = computed(() => {
                const active = activeQueueItems.value;
                if (active.length === 0) return 0;
                const total = active.reduce((sum, item) => {
                    if (item.status === 'done') return sum + 100;
                    return sum + toNumber(item.progress);
                }, 0);
                return Math.round(total / active.length);
            });

            const queueCounts = computed(() => uploadQueue.value.reduce((acc, item) => {
                acc.total += 1;
                acc[item.status] = (acc[item.status] || 0) + 1;
                return acc;
            }, { total: 0, queued: 0, uploading: 0, done: 0, error: 0, skipped: 0 }));

            const queueSummaryText = computed(() => {
                const counts = queueCounts.value;
                const parts = [];
                if (counts.uploading > 0) parts.push(t('shared_queue_uploading', 'Uploading: {count}', { count: counts.uploading }));
                if (counts.queued > 0) parts.push(t('shared_queue_queued', 'Queued: {count}', { count: counts.queued }));
                if (counts.done > 0) parts.push(t('shared_queue_done', 'Done: {count}', { count: counts.done }));
                if (counts.error > 0) parts.push(t('shared_queue_error', 'Errors: {count}', { count: counts.error }));
                if (parts.length === 0) {
                    return t('shared_queue_total', '{count} files', { count: counts.total });
                }
                return parts.join(' · ');
            });

            const hasCompletedUploads = computed(() => uploadQueue.value.some((item) => item.status === 'done' || item.status === 'skipped'));

            const clearCompleted = () => {
                uploadQueue.value = uploadQueue.value.filter((item) => item.status !== 'done' && item.status !== 'skipped');
            };

            const previewableExts = new Set(['jpg','jpeg','png','gif','webp','svg','mp4','webm','ogv','mp3','wav','ogg','pdf']);
            const getExt = (name) => (name.split('.').pop() || '').toLowerCase();

            const isPreviewable = (file) => {
                if (!file || file.type === 'dir') return false;
                return previewableExts.has(getExt(file.name || ''));
            };

            const buildPath = (file) => currentPath.value ? `${currentPath.value}/${file.name}` : file.name;

            const downloadItem = (file) => {
                if (!file || file.type === 'dir') return;
                const path = buildPath(file);
                window.location.href = Api.route('s/' + hash) + '/download?path=' + encodeURIComponent(path);
            };

            const previewItem = (file) => {
                open(file);
            };

            const open = (file) => {
                if (file.type === 'dir') {
                    const newPath = buildPath(file);
                    loadPath(newPath);
                } else {
                    if (uploadMode) {
                        return;
                    }
                    const ext = getExt(file.name || '');
                    const imgExts = ['jpg','jpeg','png','gif','webp','svg'];
                    const vidExts = ['mp4','webm','ogv'];
                    const audExts = ['mp3','wav','ogg'];

                    if (imgExts.includes(ext) || vidExts.includes(ext) || audExts.includes(ext) || ext === 'pdf') {
                        previewState.filename = file.name;
                        const path = buildPath(file);
                        previewState.src = Api.route('s/' + hash) + '/download?path=' + encodeURIComponent(path) + '&inline=1';

                        if (imgExts.includes(ext)) previewState.type = 'image';
                        else if (vidExts.includes(ext)) previewState.type = 'video';
                        else if (audExts.includes(ext)) previewState.type = 'audio';
                        else if (ext === 'pdf') previewState.type = 'pdf';

                        previewModal.show();
                    } else {
                        // Download file
                        downloadItem(file);
                    }
                }
            };

            const goUp = () => {
                if (!currentPath.value) return;
                const parts = currentPath.value.split('/');
                parts.pop();
                loadPath(parts.join('/'));
            };

            const navigateTo = (path) => {
                loadPath(path || '');
            };

            const breadcrumbs = computed(() => {
                if (!currentPath.value) {
                    return [{ label: t('shared_root', 'Root'), path: '' }];
                }
                const parts = currentPath.value.split('/').filter(Boolean);
                const crumbs = [{ label: t('shared_root', 'Root'), path: '' }];
                let acc = '';
                for (const part of parts) {
                    acc = acc ? `${acc}/${part}` : part;
                    crumbs.push({ label: part, path: acc });
                }
                return crumbs;
            });

            const filteredFiles = computed(() => {
                const q = searchQuery.value.trim().toLowerCase();
                const base = q
                    ? files.value.filter((f) => (f.name || '').toLowerCase().includes(q))
                    : files.value.slice();

                const dirFirst = (a, b) => {
                    if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                    return 0;
                };

                const byName = (a, b) => (a.name || '').localeCompare(b.name || '');
                const byMtime = (a, b) => (Number(a.mtime) || 0) - (Number(b.mtime) || 0);
                const bySize = (a, b) => (Number(a.size) || 0) - (Number(b.size) || 0);

                base.sort((a, b) => {
                    const df = dirFirst(a, b);
                    if (df !== 0 && sortKey.value !== 'name') return df;

                    switch (sortKey.value) {
                        case 'name':
                            return byName(a, b);
                        case 'mtime_asc':
                            return byMtime(a, b) || byName(a, b);
                        case 'mtime_desc':
                            return -byMtime(a, b) || byName(a, b);
                        case 'size_asc':
                            return bySize(a, b) || byName(a, b);
                        case 'size_desc':
                            return -bySize(a, b) || byName(a, b);
                        case 'type_name':
                        default:
                            return df || byName(a, b);
                    }
                });

                return base;
            });

            const summaryText = computed(() => {
                if (isFile) return '';
                const count = filteredFiles.value.length;
                const size = filteredFiles.value.reduce((acc, f) => acc + (f.type === 'dir' ? 0 : (Number(f.size) || 0)), 0);
                const countLabel = count === 1
                    ? t('shared_item_singular', 'item')
                    : t('shared_item_plural', 'items');
                return `${count} ${countLabel} · ${formatSize(size)}`;
            });

            const fileExtLabel = (name) => {
                const ext = (name.split('.').pop() || '').toUpperCase();
                if (!ext) return t('shared_file', 'File');
                return t('shared_file_ext', '{ext} file', { ext });
            };

            const formatRelativeDate = (tsValue) => {
                const ts = Number(tsValue) || 0;
                if (!ts) return t('shared_none', '—');
                const diffSec = Math.max(0, Math.floor(Date.now() / 1000) - ts);
                const day = 86400;
                const hour = 3600;
                if (diffSec < 60) {
                    return t('shared_just_now', 'just now');
                }
                if (diffSec < hour) {
                    const mins = Math.max(1, Math.floor(diffSec / 60));
                    return t('shared_minutes_ago', '{count}m ago', { count: mins });
                }
                if (diffSec < day) {
                    const hours = Math.floor(diffSec / hour);
                    return t('shared_hours_ago', '{count}h ago', { count: hours });
                }
                const days = Math.floor(diffSec / day);
                if (days < 30) return t('shared_days_ago', '{count}d ago', { count: days });
                return formatDate(ts);
            };

            const getIcon = (f) => {
                if (f.type === 'dir') return 'ri-folder-fill text-warning';
                const ext = f.name.split('.').pop().toLowerCase();
                if (['jpg','jpeg','png','gif','svg','webp'].includes(ext)) return 'ri-image-fill text-success';
                if (['mp4','webm','ogv'].includes(ext)) return 'ri-movie-fill text-info';
                if (['pdf'].includes(ext)) return 'ri-file-pdf-line text-danger';
                return 'ri-file-line text-secondary';
            };

            const formatSize = (b) => {
                if (b === 0 || !b) return t('shared_none', '-');
                const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(b) / Math.log(k));
                return parseFloat((b / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };
            const formatDate = (t) => new Date(t * 1000).toLocaleDateString();

            onMounted(() => {
                loadPath();
                previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            });

            return {
                share,
                baseUrl,
                hash,
                isFile,
                shareTitle,
                shareModeLabel,
                shareExpiresAt,
                shareExpiresDate,
                shareSender,
                shareCreatedBy,
                filename,
                size,
                locale,
                t,
                uploadMode,
                uploadMaxFileMb,
                fileInput,
                dropzoneActive,
                uploading,
                uploadMessage,
                uploadError,
                policyMaxFileMb,
                hasAllowedExtensions,
                allowedExtensionsLabel,
                hasQuotaLimit,
                quotaRemainingBytes,
                hasFileCountLimit,
                filesRemaining,
                overallProgress,
                uploadQueue,
                queueSummaryText,
                hasCompletedUploads,
                clearCompleted,
                queueStatusLabel,
                queueStatusBadgeClass,
                queueProgressBarClass,
                openFilePicker,
                onFileInputChange,
                onDragEnter,
                onDragOver,
                onDragLeave,
                onDrop,
                files,
                filteredFiles,
                loading,
                currentPath,
                searchQuery,
                sortKey,
                breadcrumbs,
                summaryText,
                open,
                goUp,
                navigateTo,
                getIcon,
                isPreviewable,
                downloadItem,
                previewItem,
                formatSize,
                formatDate,
                formatRelativeDate,
                fileExtLabel,
                previewState
            };
        }
    }).mount('#app');
}
