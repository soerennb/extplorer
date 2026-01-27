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
        body {
            min-height: 100vh;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
        }
        .admin-shell {
            max-width: 1280px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }
        .admin-shell-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .admin-note { font-size: 0.75rem; }
        .admin-badge { font-size: 0.7rem; }
        .admin-table-scroll { max-height: 55vh; }
        .admin-log-path { max-width: 240px; }
        .admin-meta-label { width: 220px; }
        .admin-config-box { max-height: 200px; overflow-y: auto; }
        .select-auto-width { width: auto; }

        /* Embedded admin modal styling */
        #userAdminModal.admin-embed {
            display: block;
            position: static;
        }
        #userAdminModal.admin-embed .modal-dialog {
            max-width: 1200px;
            margin: 0 auto 1.5rem auto;
        }
        #userAdminModal.admin-embed .modal-content {
            min-height: calc(100vh - 6rem);
            border: 1px solid var(--bs-border-color);
        }
        #userAdminModal.admin-embed .modal-header {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <div class="admin-shell-header">
            <div>
                <h1 class="h4 mb-1">Admin Panel</h1>
                <div class="text-muted small">Manage users, settings, logs, and system info.</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="<?= base_url() ?>">
                    <i class="ri-arrow-left-line me-1"></i> Back To Files
                </a>
                <a class="btn btn-outline-danger btn-sm" href="<?= base_url('logout') ?>">
                    <i class="ri-logout-box-r-line me-1"></i> Logout
                </a>
            </div>
        </div>

        <div id="adminApp">
            <user-admin ref="userAdmin"></user-admin>
        </div>
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
        window.adminEmbed = true;
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
    <script src="<?= base_url('assets/js/api.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/UserAdmin.js?v=' . config('App')->version) ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (function() {
            const app = Vue.createApp({
                mounted() {
                    const admin = this.$refs.userAdmin;
                    if (admin && typeof admin.open === 'function') {
                        admin.open();
                    }
                }
            });
            app.component('user-admin', UserAdmin);
            app.mount('#adminApp');
        })();
    </script>
</body>
</html>

