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
            <h3>Critical Configuration Error</h3>
            <p>The application cannot start because the <code>writable</code> directory is not writable by the web server.</p>
            
            <p><strong>Path:</strong> <?= WRITEPATH ?></p>
            
            <hr>
            <h5>Solution:</h5>
            <p>Run the following command on your server:</p>
            <pre class="bg-light p-3">chmod -R 0777 writable</pre>
            <p class="text-muted small">(Or chown it to the web user, e.g., www-data)</p>
            
            <a href="<?= site_url('install') ?>" class="btn btn-primary mt-3">Retry</a>
        </div>
    </div>
</body>
</html>
