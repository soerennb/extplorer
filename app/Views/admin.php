<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eXtplorer Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; }
        body { margin: 0; background: var(--bs-body-bg); color: var(--bs-body-color); }

        .admin-page { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 240px;
            display: flex;
            flex-direction: column;
            background: var(--bs-tertiary-bg);
        }
        .admin-sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .admin-sidebar-nav { padding: 0.5rem; gap: 0.15rem; }
        .admin-sidebar-nav .nav-link {
            border-radius: 0.5rem;
            color: var(--bs-body-color);
            padding: 0.5rem 0.75rem;
        }
        .admin-sidebar-nav .nav-link:hover { background: var(--bs-secondary-bg); }
        .admin-sidebar-nav .nav-link.active {
            background: var(--bs-primary);
            color: #fff;
        }
        .admin-sidebar-footer {
            margin-top: auto;
            padding: 0.75rem 1rem 1rem 1rem;
            border-top: 1px solid var(--bs-border-color);
        }

        .admin-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .admin-main-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-body-bg);
        }
        .admin-main-body { padding: 1.25rem; }

        @media (max-width: 991.98px) {
            .admin-page { flex-direction: column; }
            .admin-sidebar { width: 100%; border-right: none !important; border-bottom: 1px solid var(--bs-border-color); }
            .admin-main-header { position: static; }
        }

        .admin-note { font-size: 0.75rem; }
        .admin-badge { font-size: 0.7rem; }
        .admin-table-scroll { max-height: 55vh; }
        .admin-log-path { max-width: 240px; }
        .admin-meta-label { width: 220px; }
        .admin-config-box { max-height: 200px; overflow-y: auto; }
        .select-auto-width { width: auto; }
    </style>
</head>
<body>
    <div id="adminApp">
        <admin-app></admin-app>
    </div>

    <script <?= csp_script_nonce() ?>>
        window.baseUrl = "<?= base_url() ?>";
        window.appVersion = "<?= config('App')->version ?>";
        window.userRole = "<?= session('role') ?>";
        window.username = "<?= session('username') ?>";
        window.userPermissions = <?= json_encode(session('permissions') ?? []) ?>;
        window.csrfTokenName = "<?= csrf_token() ?>";
        window.csrfHash = "<?= csrf_hash() ?>";
        window.cspStyleNonce = "<?= service('csp')->getStyleNonce() ?>";
        window.adminPage = true;
    </script>

    <script src="<?= base_url('assets/js/vue.global.js') ?>"></script>
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
    <script src="<?= base_url('assets/js/i18n.js') ?>"></script>
    <script src="<?= base_url('assets/js/api.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminUsers.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminGroups.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminRoles.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminLogs.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminSettings.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminSystem.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/AdminApp.js?v=' . config('App')->version) ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (async function() {
            if (typeof i18n !== 'undefined' && typeof i18n.init === 'function') {
                await i18n.init();
            }
            const app = Vue.createApp({});
            app.component('admin-app', AdminApp);
            app.component('admin-users', AdminUsers);
            app.component('admin-groups', AdminGroups);
            app.component('admin-roles', AdminRoles);
            app.component('admin-logs', AdminLogs);
            app.component('admin-settings', AdminSettings);
            app.component('admin-system', AdminSystem);
            app.mount('#adminApp');
        })();
    </script>
</body>
</html>
