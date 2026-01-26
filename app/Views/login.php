<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eXtplorer 3</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <style <?= csp_style_nonce() ?>>
        :root { color-scheme: light; }
        body, html { height: 100%; }
        body {
            margin: 0;
            background: radial-gradient(circle at top left, #f3f7ff 0%, #f7f7f7 45%, #eef1f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-shell {
            width: min(960px, 94vw);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(23, 30, 43, 0.18);
            background: #fff;
        }
        .login-side {
            background: linear-gradient(135deg, #1b2a4e 0%, #233a6b 50%, #2a4a83 100%);
            color: #e7ecf7;
            min-height: 100%;
        }
        .login-side .badge {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #e7ecf7;
        }
        .login-panel {
            background: #fff;
        }
        .login-logo { width: 160px; height: auto; }
        .login-subtitle { color: #6b7280; }
        .form-hint { color: #7b8190; }
        .remote-card { background: #f6f8fb; border: 1px solid #e6e9f0; }
        .btn-check:checked + .btn-outline-primary {
            background-color: #1f4f9a;
            border-color: #1f4f9a;
            color: #fff;
        }
        @media (max-width: 991.98px) {
            .login-side { display: none; }
            .login-shell { border-radius: 14px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="row g-0">
            <div class="col-lg-5 login-side p-4 p-lg-5 d-flex flex-column justify-content-between">
                <div>
                    <img src="<?= base_url('logo-dark.svg') ?>" alt="eXtplorer 3" class="login-logo mb-4">
                    <h2 class="fw-semibold mb-2">Welcome back</h2>
                    <p class="mb-4">Secure, fast file access across local and remote mounts.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill px-3 py-2">Versioned edits</span>
                        <span class="badge rounded-pill px-3 py-2">Smart sharing</span>
                        <span class="badge rounded-pill px-3 py-2">Mounts & WebDAV</span>
                    </div>
                </div>
                <div class="small text-white-50">
                    <i class="ri-shield-check-line me-1"></i> Enforced CSP, 2FA, and access controls
                </div>
            </div>
            <div class="col-lg-7 login-panel p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="fw-semibold mb-1">Sign in</h3>
                        <div class="login-subtitle">Choose a connection mode and continue.</div>
                    </div>
                    <span class="badge text-bg-light border">eXtplorer 3</span>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>

                <form action="<?= site_url('login/auth') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-user-line"></i></span>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-lock-line"></i></span>
                                <input type="password" name="password" class="form-control" required value="<?= old('password') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Connection Mode</label>
                        <div class="btn-group btn-group-sm w-100" role="group" aria-label="Connection mode">
                            <input type="radio" class="btn-check" name="mode" id="mode_local" value="local" checked>
                            <label class="btn btn-outline-primary" for="mode_local">Local</label>
                            <input type="radio" class="btn-check" name="mode" id="mode_ftp" value="ftp">
                            <label class="btn btn-outline-primary" for="mode_ftp">FTP</label>
                            <input type="radio" class="btn-check" name="mode" id="mode_sftp" value="sftp">
                            <label class="btn btn-outline-primary" for="mode_sftp">SFTP</label>
                        </div>
                        <div class="form-hint small mt-1">Remote requires host and port.</div>
                    </div>
                    
                    <div id="remote_fields" class="collapse">
                        <div class="remote-card rounded-3 p-3 mb-4">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Host</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-global-line"></i></span>
                                        <input type="text" name="remote_host" class="form-control" placeholder="example.com">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="remote_port" id="remote_port" class="form-control" value="21">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (session()->getFlashdata('2fa_required')): ?>
                    <div class="mb-4">
                        <label class="form-label text-primary fw-bold">Two-Factor Code</label>
                        <input type="text" name="2fa_code" class="form-control" placeholder="000 000" autofocus autocomplete="off">
                        <div class="form-text">Enter the code from your authenticator app.</div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
                    <?php if (!empty($show_default_creds)): ?>
                    <div class="text-center mt-3 text-muted small">
                        Default Local: admin / admin
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script <?= csp_script_nonce() ?>>
        const remoteFields = document.getElementById('remote_fields');
        const port = document.getElementById('remote_port');
        const modes = document.querySelectorAll('input[name="mode"]');

        function updateRemoteFields(val) {
            if (val === 'ftp' || val === 'sftp') {
                remoteFields.classList.add('show');
            } else {
                remoteFields.classList.remove('show');
            }
            if (val === 'ftp') port.value = 21;
            if (val === 'sftp') port.value = 22;
        }

        modes.forEach((el) => {
            el.addEventListener('change', () => updateRemoteFields(el.value));
        });

        const checked = document.querySelector('input[name="mode"]:checked');
        if (checked) updateRemoteFields(checked.value);
    </script>
</body>
</html>
