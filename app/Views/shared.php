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
                ? ($filename ?? ($share['display_name'] ?? $st('shared_item', 'Shared Item')))
                : ($share['display_name'] ?? $st('shared_content', 'Shared Content'))
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
        $shareCreatedBy = null;
        $shareSender = null;
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
    <script src="<?= base_url('assets/js/api.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/shared.js?v=' . config('App')->version) ?>"></script>
    <script <?= csp_script_nonce() ?>>
        createSharedApp({
            hash: <?= json_encode($hash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            baseUrl: <?= json_encode(rtrim(site_url(), '/') . '/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            csrfTokenName: <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            csrfHash: <?= json_encode(csrf_hash(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            isFile: <?= json_encode((bool)$is_file) ?>,
            share: <?= json_encode($share, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            locale: <?= json_encode($locale, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            translations: <?= json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            uploadMaxFileMb: <?= (int)$uploadMaxFileMb ?>,
            uploadMode: <?= json_encode((bool)$isUploadMode) ?>,
            uploadPolicy: <?= json_encode($uploadPolicy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            shareTitle: <?= json_encode($shareTitle, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            shareModeLabel: <?= json_encode($shareModeLabel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            shareExpiresAt: <?= json_encode((bool)$shareExpiresAt) ?>,
            shareExpiresDate: <?= json_encode($shareExpiresAt ? date('Y-m-d', (int)$shareExpiresAt) : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            shareSender: <?= json_encode($shareSender, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            shareCreatedBy: <?= json_encode($shareCreatedBy, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            filename: <?= json_encode($filename ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            size: <?= json_encode((int)($size ?? 0)) ?>,
        });
    </script>
</body>
</html>
