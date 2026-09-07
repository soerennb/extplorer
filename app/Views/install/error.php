<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installation Error</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body { padding-top: 50px; background-color: #f8f9fa; }
        .error-card { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; border-left: 5px solid red; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card">
            <h3>Installation unavailable</h3>
            <p><?= esc($error ?? 'The application cannot be initialized safely.') ?></p>
            <p class="text-muted small">Check the writable directory permissions and the application logs. Do not make the directory world-writable.</p>
            
            <a href="<?= site_url('install') ?>" class="btn btn-primary mt-3">Retry</a>
        </div>
    </div>
</body>
</html>
