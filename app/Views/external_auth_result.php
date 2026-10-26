<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(lang('ExternalAuth.title')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <h1 class="h4"><?= esc(lang('ExternalAuth.title')) ?></h1>
        <p role="status"><?= esc(lang(!empty($success) ? 'ExternalAuth.complete' : 'ExternalAuth.failed')) ?></p>
        <?php if (empty($success)): ?>
        <a class="btn btn-primary" href="<?= site_url('login') ?>?local=1"><?= esc(lang('ExternalAuth.local_login')) ?></a>
        <?php endif; ?>
    </main>
</body>
</html>
