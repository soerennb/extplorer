<?php
if (!isset($logout_request) || !$logout_request instanceof \App\Services\ExternalAuth\LogoutRequest) {
    throw new \RuntimeException('Provider logout request is missing.');
}
?>
<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(lang('ExternalAuth.logout_title')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <script src="<?= base_url('assets/js/external-logout.js') ?>" defer></script>
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <h1 class="h4"><?= esc(lang('ExternalAuth.logout_title')) ?></h1>
        <p><?= esc(lang('ExternalAuth.logout_notice')) ?></p>
        <form id="external-logout-form" method="post" action="<?= esc($logout_request->url) ?>">
            <?php foreach ($logout_request->parameters as $name => $value): ?>
            <input type="hidden" name="<?= esc($name) ?>" value="<?= esc($value) ?>">
            <?php endforeach; ?>
            <button class="btn btn-primary" type="submit"><?= esc(lang('ExternalAuth.logout_continue')) ?></button>
        </form>
        <a class="d-inline-block mt-3" href="<?= site_url('login') ?>?logged_out=1"><?= esc(lang('ExternalAuth.local_login')) ?></a>
    </main>
</body>
</html>
