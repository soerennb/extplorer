<?php

return [
    'invalid_email_settings' => 'Check the email protocol, server, port, encryption and sender fields.',
    'email_not_configured' => 'Complete and save the email configuration first.',
    'sendmail_path_read_only' => 'The Sendmail program is configured by the server operator.',
    'sendmail_migration_required' => 'The server operator must configure the previous custom Sendmail program before delivery can resume.',
    'sendmail_unavailable' => 'A trusted Sendmail program is not available. Contact the server operator.',
    'email_saved_settings_only' => 'Save the email settings first. Mail checks use only the saved configuration.',
    'email_recipient_required' => 'Enter a valid recipient email address.',
    'email_delivery_failed' => 'Email delivery failed. Check the saved configuration and try again.',
    'email_validation_failed' => 'Email validation failed. Check the saved configuration and try again.',
    'email_probe_rate_limited' => 'Too many email checks. Try again in a minute.',
    'transfer_recipient_limit' => 'Too many transfer recipients this hour. Please try again later.',
];
