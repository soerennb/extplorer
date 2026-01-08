<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eXtplorer 3</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style>
        body, html { height: 100%; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center mb-4">eXtplorer 3</h3>
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <form action="<?= site_url('login/auth') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Connection Mode</label>
                <select name="mode" class="form-select" onchange="toggleRemoteFields(this.value)">
                    <option value="local">Local Filesystem</option>
                    <option value="ftp">FTP Server</option>
                    <option value="sftp">SFTP (SSH)</option>
                </select>
            </div>
            
            <div id="remote_fields" style="display:none;">
                <div class="mb-3">
                    <label id="host_label" class="form-label">Host</label>
                    <input type="text" name="remote_host" class="form-control" placeholder="example.com">
                </div>
                <div class="mb-3">
                    <label id="port_label" class="form-label">Port</label>
                    <input type="number" name="remote_port" id="remote_port" class="form-control" value="21">
                </div>
            </div>

            <div class="mb-3">
//...
    <script>
        function toggleRemoteFields(val) {
            const fields = document.getElementById('remote_fields');
            const port = document.getElementById('remote_port');
            fields.style.display = (val === 'ftp' || val === 'sftp') ? 'block' : 'none';
            if (val === 'ftp') port.value = 21;
            if (val === 'sftp') port.value = 22;
        }
    </script>
</body>
</html>
