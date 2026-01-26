<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install eXtplorer 3</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .install-card { max-width: 600px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-ok { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <h2 class="text-center mb-4">eXtplorer 3 Setup</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php elseif (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <div class="mb-4">
                <h4>System Checks</h4>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PHP Version (<?= $checks['php']['current'] ?>)
                        <span class="<?= $checks['php']['status'] ? 'status-ok' : 'status-fail' ?>">
                            <?= $checks['php']['status'] ? 'OK' : 'FAIL' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Writable Directory
                        <span class="<?= $checks['writable']['status'] ? 'status-ok' : 'status-fail' ?>">
                            <?= $checks['writable']['status'] ? 'OK' : 'FAIL' ?>
                        </span>
                    </li>
                    <?php foreach ($checks['extensions'] as $ext => $loaded): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Extension: <?= $ext ?>
                        <span class="<?= $loaded ? 'status-ok' : 'status-fail' ?>">
                            <?= $loaded ? 'OK' : 'FAIL' ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($checks['php']['status'] && $checks['writable']['status']): ?>
                <form action="<?= site_url('install/create') ?>" method="post">
                    <?= csrf_field() ?>
                    <h4 class="mb-3">Create Admin Account</h4>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <div class="form-text">Must be at least 8 characters.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Install & Create Admin</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    Please fix the issues above to proceed.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
