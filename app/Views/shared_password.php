<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared - eXtplorer</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; background-color: #f8f9fa; }
</head>
<body>
    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <h5 class="mb-3">Password Required</h5>
            <p class="text-muted small mb-4">This share is protected.</p>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger small"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= site_url('s/' . $hash . '/auth') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Enter Password" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Unlock</button>
            </form>
        </div>
    </div>
</body>
</html>
