<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eXtplorer 3</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style>
        body, html { height: 100%; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 600px; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .login-logo { max-width: 200px; height: auto; display: block; margin: 0 auto 1.5rem; }
        .d-none { display: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="<?= base_url('logo.svg') ?>" alt="eXtplorer 3" class="login-logo">
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <form action="<?= site_url('login/auth') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Connection Mode</label>
                <select name="mode" id="connection_mode" class="form-select">
                    <option value="local">Local Filesystem</option>
                    <option value="ftp">FTP Server</option>
                    <option value="sftp">SFTP (SSH)</option>
                </select>
            </div>
            
            <div id="remote_fields" class="d-none">
                <div class="mb-3">
                    <label class="form-label">Host</label>
                    <input type="text" name="remote_host" class="form-control" placeholder="example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Port</label>
                    <input type="number" name="remote_port" id="remote_port" class="form-control" value="21">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
            <div class="text-center mt-3 text-muted small">
                Default Local: admin / admin
            </div>
        </form>
    </div>

    <script>
        document.getElementById('connection_mode').addEventListener('change', function() {
            toggleRemoteFields(this.value);
        });

        function toggleRemoteFields(val) {
            const fields = document.getElementById('remote_fields');
            const port = document.getElementById('remote_port');
            
            if (val === 'ftp' || val === 'sftp') {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
            }
            
            if (val === 'ftp') port.value = 21;
            if (val === 'sftp') port.value = 22;
        }
    </script>
</body>
</html>