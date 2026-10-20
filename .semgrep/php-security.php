<?php

// Fixtures for semgrep --test; this file is never executed or packaged.
// ruleid: extplorer.php.no-process-execution
exec($request->getPost('command'));
// ruleid: extplorer.php.no-process-execution
popen($settings['sendmail_path'], 'w');
// ruleid: extplorer.php.no-dynamic-code
eval($payload);
// ruleid: extplorer.php.no-dynamic-code
unserialize($payload);
// ruleid: extplorer.php.no-user-mail-program
$config['mailPath'] = $settings['sendmail_path'];
// ruleid: extplorer.php.no-user-mail-program
$email->mailPath = $settings['sendmail_path'];
// ok: extplorer.php.no-user-mail-program
$config['mailPath'] = escapeshellarg((new SendmailPolicy())->resolve($settings));
// ok: extplorer.php.no-dynamic-code
json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
