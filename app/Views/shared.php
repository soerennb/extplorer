<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $translations = is_array($translations ?? null) ? $translations : [];
        $sharedPageTitle = $translations['shared_page_title'] ?? 'Shared - eXtplorer';
    ?>
    <title><?= esc($sharedPageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <?php
        $translations = is_array($translations ?? null) ? $translations : [];
        $locale = is_string($locale ?? null) && $locale !== '' ? $locale : 'en';
        $st = static function (string $key, string $fallback = '') use ($translations): string {
            $value = $translations[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
            return $fallback !== '' ? $fallback : $key;
        };

        $shareTitle = $share['subject'] ?? (
            $is_file
                ? ($filename ?? basename($share['path'] ?? $st('shared_item', 'Shared Item')))
                : basename($share['path'] ?? $st('shared_content', 'Shared Content'))
        );
        if (($share['source'] ?? '') === 'transfer' && empty($share['subject'])) {
            $shareTitle = $st('shared_file_transfer', 'File Transfer');
        }
        $shareMode = $share['mode'] ?? 'read';
        $isUploadMode = $shareMode === 'upload';
        $uploadMaxFileMb = (int)($uploadMaxFileMb ?? 0);
        $uploadPolicy = is_array($uploadPolicy ?? null) ? $uploadPolicy : [];
        $shareModeLabels = [
            'read' => $st('shared_mode_read', 'Read'),
            'upload' => $st('shared_mode_upload', 'Upload'),
            'write' => $st('shared_mode_write', 'Write'),
        ];
        $shareModeLabel = $shareModeLabels[$shareMode] ?? ucfirst($shareMode);
        $shareExpiresAt = $share['expires_at'] ?? null;
        $shareCreatedBy = $share['created_by'] ?? null;
        $shareSender = $share['sender_email'] ?? null;
    ?>
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; background-color: #f8f9fa; }
        .shared-container { max-width: 1100px; margin: 0 auto; height: 100%; display: flex; flex-direction: column; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .shared-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .shared-brand { display: flex; align-items: center; gap: 0.75rem; }
        .shared-title { margin: 0; font-weight: 600; }
        .shared-subtitle { margin: 0.1rem 0 0 0; }
        .shared-meta { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
        .shared-meta .badge { font-weight: 500; }
        .shared-meta-pill { background: var(--bs-tertiary-bg); color: var(--bs-body-color); border: 1px solid var(--bs-border-color); }
        .shared-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem; }
        .shared-actions .btn { white-space: nowrap; }
        .file-list { flex: 1; overflow-y: auto; padding: 1rem; }
        .shared-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.25rem 0.25rem 0.75rem 0.25rem; border-bottom: 1px solid #f0f0f0; margin-bottom: 0.75rem; }
        .shared-toolbar-left { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .shared-toolbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .select-auto-width { width: auto; }
        .shared-breadcrumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem; padding: 0.25rem 0.25rem 0.5rem 0.25rem; }
        .shared-breadcrumbs .btn { padding: 0; }
        .shared-upload-panel { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.85rem 1rem; border: 1px dashed var(--bs-warning); background: rgba(255, 193, 7, 0.08); border-radius: 0.75rem; margin: 0.25rem 0.25rem 0.75rem 0.25rem; }
        .shared-upload-panel-icon { font-size: 1.6rem; color: var(--bs-warning); line-height: 1; }
        .shared-upload-panel-title { font-weight: 600; margin-bottom: 0.15rem; }
        .shared-upload-panel-note { font-size: 0.85rem; color: var(--bs-secondary-color); margin-top: 0.35rem; }
        .shared-dropzone { margin-top: 0.6rem; border: 1px dashed var(--bs-border-color); border-radius: 0.75rem; background: var(--bs-body-bg); padding: 1rem; text-align: center; transition: border-color 0.15s ease, background-color 0.15s ease; }
        .shared-dropzone.active { border-color: var(--bs-primary); background: rgba(13, 110, 253, 0.05); }
        .shared-dropzone-title { font-weight: 600; }
        .shared-dropzone-subtitle { font-size: 0.9rem; color: var(--bs-secondary-color); margin-top: 0.15rem; }
        .shared-upload-meta { font-size: 0.8rem; color: var(--bs-secondary-color); margin-top: 0.5rem; }
        .shared-upload-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-top: 0.75rem; }
        .shared-upload-limits { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.6rem; }
        .shared-upload-limit-pill { background: var(--bs-tertiary-bg); color: var(--bs-body-color); border: 1px solid var(--bs-border-color); font-weight: 500; }
        .shared-upload-queue { margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--bs-border-color); }
        .shared-upload-queue-item { padding: 0.55rem 0; border-bottom: 1px dashed var(--bs-border-color); }
        .shared-upload-queue-item:last-child { border-bottom: none; }
        .shared-upload-queue-name { font-weight: 600; word-break: break-word; }
        .shared-upload-queue-meta { font-size: 0.8rem; color: var(--bs-secondary-color); }
        .shared-upload-queue-status { min-width: 90px; text-align: right; }
        .shared-upload-queue-progress { margin-top: 0.35rem; }
        .progress-compact { height: 6px; }
        .file-item { display: flex; align-items: center; padding: 0.75rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; }
        .file-item:hover { background-color: #f8f9fa; }
        .file-item-name { flex: 1; min-width: 0; }
        .file-name { flex: 1; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-submeta { font-size: 0.8rem; color: #9aa0a6; margin-top: 0.1rem; }
        .file-icon { font-size: 1.5rem; margin-right: 1rem; color: #6c757d; }
        .file-meta { font-size: 0.85rem; color: #adb5bd; }
        .file-actions { display: flex; align-items: center; gap: 0.25rem; margin-left: 0.5rem; }
        .file-actions .btn { opacity: 0.85; }
        .file-actions .btn:hover { opacity: 1; }
        .preview-box { display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; }
        .preview-icon { font-size: 5rem; color: #dee2e6; }
        .max-h-80vh { max-height: 80vh; }
        .preview-modal-body { min-height: 400px; background: #000; }
        .preview-pdf-frame { height: 70vh; border: none; }
        .shared-empty { text-align: center; color: #6c757d; padding: 3rem 1rem; }
        .shared-empty i { font-size: 2.5rem; color: #c2c7cc; display: block; margin-bottom: 0.5rem; }
        .progress-w-0 { width: 0%; }
        .progress-w-5 { width: 5%; }
        .progress-w-10 { width: 10%; }
        .progress-w-15 { width: 15%; }
        .progress-w-20 { width: 20%; }
        .progress-w-25 { width: 25%; }
        .progress-w-30 { width: 30%; }
        .progress-w-35 { width: 35%; }
        .progress-w-40 { width: 40%; }
        .progress-w-45 { width: 45%; }
        .progress-w-50 { width: 50%; }
        .progress-w-55 { width: 55%; }
        .progress-w-60 { width: 60%; }
        .progress-w-65 { width: 65%; }
        .progress-w-70 { width: 70%; }
        .progress-w-75 { width: 75%; }
        .progress-w-80 { width: 80%; }
        .progress-w-85 { width: 85%; }
        .progress-w-90 { width: 90%; }
        .progress-w-95 { width: 95%; }
        .progress-w-100 { width: 100%; }

        @media (max-width: 767.98px) {
            .shared-container { max-width: 100%; min-height: 100vh; box-shadow: none; }
            .shared-header { flex-direction: column; align-items: stretch; }
            .shared-actions { align-items: stretch; }
        }
    </style>
</head>
<body>
    <?= view('shared_template') ?>
    <script src="<?= base_url('assets/js/vue.runtime.global.prod.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/compiled/shared-render.js?v=' . config('App')->version) ?>"></script>
    <script <?= csp_script_nonce() ?>>
        const { createApp, ref, reactive, computed, onMounted } = Vue;
        const hash = <?= json_encode($hash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const baseUrl = <?= json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const isFile = <?= json_encode((bool)$is_file) ?>;
        const share = <?= json_encode($share, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const locale = <?= json_encode($locale, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const translations = <?= json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const uploadMaxFileMb = <?= (int)$uploadMaxFileMb ?>;
        const uploadMode = <?= json_encode((bool)$isUploadMode) ?>;
        const uploadPolicy = <?= json_encode($uploadPolicy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareTitle = <?= json_encode($shareTitle, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareModeLabel = <?= json_encode($shareModeLabel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareExpiresAt = <?= json_encode((bool)$shareExpiresAt) ?>;
        const shareExpiresDate = <?= json_encode($shareExpiresAt ? date('Y-m-d', (int)$shareExpiresAt) : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareSender = <?= json_encode($shareSender ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareCreatedBy = <?= json_encode($shareCreatedBy ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const filename = <?= json_encode($filename ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const size = <?= json_encode((int)($size ?? 0)) ?>;

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
                        const res = await fetch(baseUrl + 's/' + hash + '/ls?path=' + encodeURIComponent(path))
                            .then(r => r.json());
                        
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

                const safeParseJson = (text) => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        return {};
                    }
                };

                const uploadItem = (item) => new Promise((resolve) => {
                    item.status = 'uploading';
                    item.error = '';
                    item.progress = 0;

                    const fd = new FormData();
                    fd.append('file', item.file);
                    fd.append('path', currentPath.value || '');

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', baseUrl + 's/' + hash + '/upload');
                    xhr.withCredentials = true;

                    xhr.upload.onprogress = (event) => {
                        if (!event.lengthComputable) return;
                        const percent = event.total > 0 ? Math.round((event.loaded / event.total) * 100) : 0;
                        item.progress = percent;
                    };

                    xhr.onload = () => {
                        const json = safeParseJson(xhr.responseText || '{}');
                        if (xhr.status >= 200 && xhr.status < 300) {
                            item.status = 'done';
                            item.progress = 100;
                            if (json?.upload_policy && typeof json.upload_policy === 'object') {
                                uploadPolicyState.value = json.upload_policy;
                            }
                            resolve({ ok: true, json });
                            return;
                        }

                        const message = json?.messages?.error || json?.message || t('shared_upload_error', 'Upload failed.');
                        item.status = 'error';
                        item.error = message;
                        resolve({ ok: false, message });
                    };

                    xhr.onerror = () => {
                        const message = t('shared_upload_error', 'Upload failed.');
                        item.status = 'error';
                        item.error = message;
                        resolve({ ok: false, message });
                    };

                    xhr.send(fd);
                });

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
                    window.location.href = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path);
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
                            previewState.src = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path) + '&inline=1';
                            
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
    </script>
</body>
</html>
