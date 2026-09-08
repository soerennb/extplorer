<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eXtplorer</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/diff2html.min.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; overflow: hidden; }
        #app { display: flex; flex-direction: column; height: 100%; }
        .main-container { flex: 1; display: flex; overflow: hidden; position: relative; }
        .content-area { flex: 1; overflow: auto; min-width: 0; }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            border-right: 1px solid var(--bs-border-color);
            overflow-y: auto;
            background-color: var(--bs-body-bg);
            flex-shrink: 0;
        }
        .details-pane {
            width: 320px;
            flex: 0 0 320px;
            min-width: 320px;
            border-left: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            overflow-y: auto;
            flex-shrink: 0;
        }
        .details-preview {
            min-height: 120px;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
        }
        .details-preview-icon {
            font-size: 3rem;
        }
        .bookmark-item-action {
            opacity: 0.75;
        }

        @media (max-width: 991.98px) {
            .sidebar { width: auto; border-right: none; }
            .details-pane {
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                width: min(86vw, 340px);
                z-index: 1030;
                box-shadow: -0.5rem 0 1.5rem rgba(0, 0, 0, 0.16);
            }
        }
        
        /* Main Container Drag Over */
        .main-container.drag-over { background-color: var(--bs-primary-bg-subtle); border: 2px dashed var(--bs-primary); }
        .drag-operation-indicator {
            position: absolute;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1045;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(var(--bs-info-rgb), 0.35);
            background: rgba(var(--bs-body-bg-rgb), 0.92);
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.12);
            color: var(--bs-body-color);
            font-size: 0.9rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }
        .drag-operation-indicator-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 50%;
            background: rgba(var(--bs-info-rgb), 0.14);
            color: var(--bs-info);
            font-size: 1rem;
        }
        .drag-operation-indicator-copy {
            border-color: rgba(var(--bs-success-rgb), 0.35);
        }
        .drag-operation-indicator-copy .drag-operation-indicator-icon {
            background: rgba(var(--bs-success-rgb), 0.14);
            color: var(--bs-success);
        }
        [data-bs-theme="dark"] .drag-operation-indicator {
            background: rgba(33, 37, 41, 0.92);
        }

        .selection-action-bar {
            border-bottom: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            padding: 0.55rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .selection-action-bar-count {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 31px;
            margin-right: auto;
            font-weight: 600;
            color: var(--bs-body-color);
        }
        .selection-action-bar-count i {
            color: var(--bs-primary);
        }
        .selection-action-bar-actions {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .command-palette-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1060;
            background: rgba(15, 23, 42, 0.38);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: min(12vh, 5rem) 1rem 1rem;
        }
        .command-palette {
            width: min(640px, 100%);
            overflow: hidden;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            background: var(--bs-body-bg);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.2);
        }
        .command-palette-search {
            border-bottom: 1px solid var(--bs-border-color);
        }
        .command-palette-list {
            max-height: min(55vh, 420px);
            overflow-y: auto;
        }
        .command-palette-item {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border: 0;
            background: transparent;
            color: var(--bs-body-color);
            text-align: left;
        }
        .command-palette-item:hover,
        .command-palette-item.active {
            background: var(--bs-tertiary-bg);
        }
        .command-palette-item:disabled {
            color: var(--bs-secondary-color);
            cursor: not-allowed;
        }
        .command-palette-icon {
            width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.35rem;
            background: var(--bs-secondary-bg);
            color: var(--bs-primary);
            flex: 0 0 auto;
        }
        @media (max-width: 575.98px) {
            .selection-action-bar {
                align-items: stretch;
            }
            .selection-action-bar-count {
                width: 100%;
            }
            .selection-action-bar-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .selection-action-bar-actions .btn {
                flex: 1 1 auto;
            }
        }

        .navbar-logo { height: 48px; width: auto; margin-right: 10px; }
        .mobile-current-path {
            min-width: 0;
            max-width: 42vw;
        }
        .mobile-current-path-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 575.98px) {
            .app-navbar {
                min-height: 48px;
            }
            .app-navbar .container-fluid {
                gap: 0.35rem;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .navbar-logo {
                height: 30px;
                margin-right: 0;
            }
            .app-navbar .btn-sm {
                --bs-btn-padding-y: 0.25rem;
                --bs-btn-padding-x: 0.45rem;
            }
            .desktop-path,
            .navbar-search {
                display: none !important;
            }
            .app-toolbar {
                padding: 0.5rem !important;
                gap: 0.5rem !important;
                flex-wrap: nowrap !important;
            }
            .app-toolbar-primary {
                flex: 1 1 auto;
                justify-content: center;
            }
            .app-toolbar-overflow {
                flex: 0 0 auto;
            }
            .app-statusbar {
                padding: 0.45rem 0.6rem !important;
                gap: 0.5rem !important;
            }
            .app-statusbar-summary {
                min-width: 0;
                flex: 1 1 auto;
            }
            .app-statusbar-summary .connection-label,
            .app-statusbar-summary .version-label {
                display: none;
            }
            .app-statusbar-pagesize {
                margin-left: 0 !important;
            }
            .app-statusbar-pagesize .form-select {
                max-width: 76px;
            }
            .app-statusbar-pagination {
                width: 100%;
                order: 3 !important;
                justify-content: center;
            }
            .app-statusbar-pagination .pagination {
                --bs-pagination-padding-x: 0.45rem;
                --bs-pagination-padding-y: 0.25rem;
                --bs-pagination-font-size: 0.8rem;
            }
        }
        
        .file-item { 
            cursor: pointer; 
            border-radius: 4px;
            transition: background-color 0.2s;
            color: var(--bs-body-color);
        }
        .file-item:hover { background-color: var(--bs-tertiary-bg); }
        .file-item.selected { background-color: var(--bs-primary) !important; color: white !important; }
        .file-item.selected .file-meta { color: rgba(255,255,255,0.8) !important; }
        .file-item.drag-over { background-color: var(--bs-info-bg-subtle) !important; border: 1px dashed var(--bs-info) !important; }
        .mount-badge { font-size: 0.65rem; }
        
        /* Grid View */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            align-content: start;
            width: 100%;
        }
        .grid-view .file-item { 
            width: auto; 
            padding: 10px; 
            margin: 0;
            text-align: center; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        @media (min-width: 768px) {
            .grid-view { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; }
            .grid-view .file-item { padding: 15px; }
        }

        .grid-view .file-icon { font-size: 2.5rem; line-height: 1; margin-bottom: 5px; }
        @media (min-width: 768px) { .grid-view .file-icon { font-size: 3rem; } }

        .grid-view .file-name { 
            width: 100%; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            font-size: 0.8rem;
        }
        @media (min-width: 768px) { .grid-view .file-name { font-size: 0.9rem; } }

        /* List View */
        .list-view {
            width: 100%;
        }
        .list-view,
        .list-view-header {
            --list-name-min: clamp(200px, 30vw, 420px);
            --list-size: clamp(90px, 10vw, 120px);
            --list-date: clamp(140px, 16vw, 210px);
            --list-cols: 32px minmax(var(--list-name-min), 1fr);
        }
        @media (min-width: 768px) {
            .list-view,
            .list-view-header {
                --list-cols: 32px minmax(var(--list-name-min), 1fr) var(--list-size) var(--list-date);
            }
        }
        .list-view-header {
            display: grid;
            grid-template-columns: var(--list-cols);
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-body-bg);
            column-gap: 8px;
        }
        .content-area.scrolled .list-view-header {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .list-view .file-item {
            display: grid;
            grid-template-columns: var(--list-cols);
            align-items: center;
            padding: 8px 15px; /* Increased for touch */
            border-bottom: 1px solid var(--bs-border-color);
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            min-height: 44px; /* Touch target minimum */
            column-gap: 8px;
        }
        @media (min-width: 768px) {
            .list-view .file-item { padding: 2px 15px; min-height: 32px; }
        }

        .list-view .file-icon { 
            font-size: 1.2rem; 
            width: 24px; 
            min-width: 24px;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            justify-self: center;
        }
        .list-view .file-name { 
            flex: 1; 
            text-overflow: ellipsis; 
            overflow: hidden;
            line-height: 1.2;
            min-width: 0;
        }
        
        .list-view .file-meta-col {
            display: none; /* Hide on mobile */
            font-size: 0.8rem; 
            color: var(--bs-secondary-color); 
            margin-left: 15px; 
            text-align: right;
        }
        .list-view .file-meta-col,
        .list-view-header .file-meta-col {
            margin-left: 0;
            justify-self: end;
        }
        .list-view-name-col {
            min-width: 0;
        }
        @media (min-width: 768px) {
            .list-view .file-meta-col { display: block; }
        }
        .rotate-90 { transform: rotate(90deg); }
        
        [v-cloak] { display: none; }
        
        [data-bs-theme="dark"] .navbar { background-color: var(--bs-body-bg) !important; border-bottom: 1px solid var(--bs-border-color); }
        [data-bs-theme="light"] .navbar { background-color: var(--bs-dark) !important; }
        
        .dropdown-menu { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .icon-large { font-size: 4rem; }
        .thumb-grid { width: 100%; height: 100%; object-fit: cover; }

        .list-view-header-spacer { width: auto; }
        .cursor-pointer { cursor: pointer; }
        .h-90vh { height: 90vh; }
        .h-100 { height: 100%; }
        .w-100 { width: 100%; }
        .max-h-90vh { max-height: 90vh; }
        .z-1060 { z-index: 1060; }
        .progress-thin { height: 5px; }
        .empty-state { min-height: 50vh; }
        .empty-state-icon { width: 120px; height: 120px; }
        .empty-state-icon-glyph { font-size: 3.5rem; opacity: 0.5; }
        .empty-state-text { max-width: 450px; line-height: 1.6; }
        .shared-badge { width: 18px; height: 18px; transform: translate(20%, 20%); z-index: 1; }
        .shared-badge-icon { font-size: 12px; }
        .preview-modal-body { min-height: 400px; background: #000; }
        .preview-pdf-frame { height: 80vh; border: none; }
        .tree-indent { padding-left: 15px; }
        .tree-item-interactive { cursor: pointer; user-select: none; color: var(--bs-body-color); }
        .tree-item-interactive:hover { background-color: var(--bs-tertiary-bg); }
        .tree-toggle { transition: transform 0.2s; }
        .tree-folder { color: #ffc107; }
        .tree-folder-selected { color: #fff; }
        .transfer-dropzone { min-height: 200px; display: flex; flex-direction: column; justify-content: center; }
        .transfer-files-list { max-height: 250px; }
        .transfer-recipient { max-width: 150px; }
        .upload-list { max-height: 300px; overflow-y: auto; }
        .upload-status { width: 150px; }
        .progress-compact { height: 6px; }
        .qr-image { width: 200px; height: 200px; }
        .qr-input-group { max-width: 300px; margin: 0 auto; }
        .admin-note { font-size: 0.75rem; }
        .admin-badge { font-size: 0.7rem; }
        .admin-table-scroll { max-height: 400px; }
        .admin-log-path { max-width: 200px; }
        .admin-meta-label { width: 200px; }
        .admin-config-box { max-height: 150px; overflow-y: auto; }
        .select-auto-width { width: auto; }
        .context-menu { position: fixed; z-index: 1050; }
        .path-breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: rgba(255, 255, 255, 0.6); }
        [data-bs-theme="dark"] .file-tree-item .border-secondary-subtle { border-color: var(--bs-secondary-color) !important; }
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

        /* Profile Modal Enhancements */
        .profile-password-hints {
            padding-left: 1rem;
            font-size: 0.82rem;
        }
        .profile-strength-bar {
            height: 6px;
        }
        .strength-score-0 { width: 0%; }
        .strength-score-1 { width: 25%; }
        .strength-score-2 { width: 50%; }
        .strength-score-3 { width: 75%; }
        .strength-score-4 { width: 100%; }

        .profile-stepper {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .profile-step {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--bs-secondary-color);
        }
        .profile-step-index {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            border: 1px solid var(--bs-border-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: var(--bs-body-bg);
        }
        .profile-step.is-active .profile-step-index {
            border-color: var(--bs-primary);
            background: var(--bs-primary);
            color: #fff;
        }
        .profile-step.is-complete .profile-step-index {
            border-color: var(--bs-success);
            background: var(--bs-success);
            color: #fff;
        }
        .profile-step.is-active .profile-step-label {
            color: var(--bs-primary-text-emphasis);
            font-weight: 600;
        }
        .profile-step.is-complete .profile-step-label {
            color: var(--bs-success-text-emphasis);
            font-weight: 600;
        }

        .profile-qr-wrap {
            display: inline-block;
            padding: 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid var(--bs-border-color);
            background: #fff;
        }
        [data-bs-theme="dark"] .profile-qr-wrap {
            background: #fff;
        }
        .qr-image {
            max-width: 200px;
            height: auto;
        }
        .profile-secret-block {
            padding: 0.6rem;
            border: 1px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            background: var(--bs-tertiary-bg);
        }
        .profile-recovery-list {
            padding: 0.75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            background: #fff;
            max-height: 180px;
            overflow: auto;
        }
        [data-bs-theme="dark"] .profile-recovery-list {
            background: var(--bs-body-bg);
        }
    </style>
    <style <?= csp_style_nonce() ?> id="context-menu-style"></style>
</head>
<body>
    <?= view('app_template') ?>
    <!-- Scripts -->
    <script <?= csp_script_nonce() ?>>
        window.baseUrl = <?= json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.appVersion = <?= json_encode(config('App')->version, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.appEnvironment = <?= json_encode(ENVIRONMENT, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.userRole = <?= json_encode(session('role') ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.username = <?= json_encode(session('username') ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.userPermissions = <?= json_encode(session('permissions') ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.connectionMode = <?= json_encode(session('connection')['mode'] ?? 'local', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.forcePasswordChange = <?= json_encode((bool)session('force_password_change')) ?>;
        window.csrfTokenName = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.csrfHash = <?= json_encode(csrf_hash(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.webdavEnabled = <?= json_encode($webdavEnabled) ?>;
        window.cspStyleNonce = <?= json_encode(service('csp')->getStyleNonce(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= base_url('assets/js/vue.runtime.global.prod.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.min.js') ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (function() {
            if (!window.cspStyleNonce) return;
            const doc = document;
            const origCreateElement = doc.createElement.bind(doc);
            const origCreateElementNS = doc.createElementNS.bind(doc);

            doc.createElement = function(tagName, options) {
                const el = origCreateElement(tagName, options);
                if (String(tagName).toLowerCase() === 'style') {
                    el.setAttribute('nonce', window.cspStyleNonce);
                }
                return el;
            };
            doc.createElementNS = function(ns, tagName, options) {
                const el = origCreateElementNS(ns, tagName, options);
                if (String(tagName).toLowerCase() === 'style') {
                    el.setAttribute('nonce', window.cspStyleNonce);
                }
                return el;
            };

            window.__restoreCreateElement = function() {
                doc.createElement = origCreateElement;
                doc.createElementNS = origCreateElementNS;
                delete window.__restoreCreateElement;
            };
        })();
    </script>
    <script src="<?= base_url('assets/vendor/ace/ace.min.js') ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (function() {
            if (window.ace && window.ace.require && window.cspStyleNonce) {
                const aceDom = window.ace.require("ace/lib/dom");
                const origCreate = aceDom.createElement.bind(aceDom);
                aceDom.createElement = function(tagName, ns) {
                    const el = origCreate(tagName, ns);
                    if (String(tagName).toLowerCase() === 'style') {
                        el.setAttribute('nonce', window.cspStyleNonce);
                    }
                    return el;
                };
            }
            if (window.__restoreCreateElement) window.__restoreCreateElement();
        })();
    </script>
    <script src="<?= base_url('assets/js/diff.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/diff2html-ui.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/api.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/store.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/i18n.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/FileTree.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/UserAdmin.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/UserProfile.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/ShareModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/UploadModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/FileHistoryModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/components/TransferModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/compiled/app-render.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/app.js?v=' . config('App')->version) ?>"></script>
</body>
</html>
