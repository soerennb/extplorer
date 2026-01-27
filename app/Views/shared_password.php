<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $translations = is_array($translations ?? null) ? $translations : [];
        $st = static function (string $key, string $fallback = '') use ($translations): string {
            $value = $translations[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
            return $fallback !== '' ? $fallback : $key;
        };
        $sharedPageTitle = $st('shared_page_title', 'Shared - eXtplorer');
    ?>
    <title><?= esc($sharedPageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; background-color: #f8f9fa; }
        body { margin: 0; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
        .shared-password-card { width: 100%; max-width: 420px; border: none; border-radius: 1rem; }
        .shared-password-icon { font-size: 2.5rem; color: var(--bs-primary); }
    </style>
</head>
<body>
    <div class="card shadow-sm shared-password-card">
        <div class="card-body p-4 text-center">
            <div class="shared-password-icon mb-2">
                <i class="ri-lock-2-line"></i>
            </div>
            <h5 class="mb-2"><?= esc($st('shared_password_required', 'Password Required')) ?></h5>
            <p class="text-muted small mb-4"><?= esc($st('shared_password_protected', 'This share is protected.')) ?></p>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger small"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= site_url('s/' . $hash . '/auth') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="<?= esc($st('shared_enter_password', 'Enter Password')) ?>"
                        aria-label="<?= esc($st('shared_enter_password', 'Enter Password')) ?>"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= esc($st('shared_unlock', 'Unlock')) ?></button>
            </form>
        </div>
    </div>
</body>
</html>
