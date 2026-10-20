<?php

namespace App\Services;

/** Canonical values shared by settings writes, fingerprints and delivery. */
final class MailConfigPolicy
{
    public const FIELDS = ['email_protocol', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
        'smtp_crypto', 'email_from', 'email_from_name'];

    public function normalize(array $settings): array
    {
        $defaults = ['email_protocol' => 'smtp', 'smtp_host' => '', 'smtp_port' => 587,
            'smtp_user' => '', 'smtp_pass' => '', 'smtp_crypto' => 'tls',
            'email_from' => '', 'email_from_name' => 'eXtplorer'];
        $config = array_intersect_key(array_replace($defaults, $settings), $defaults);
        foreach ($config as $key => $value) {
            if ($key !== 'smtp_port' && !is_string($value)) throw new MailConfigurationException();
        }
        foreach (['email_protocol', 'smtp_crypto'] as $key) $config[$key] = strtolower(trim($config[$key]));
        foreach (['smtp_host', 'email_from', 'email_from_name'] as $key) $config[$key] = trim($config[$key]);
        if (!in_array($config['email_protocol'], ['smtp', 'sendmail', 'mail'], true)
            || !in_array($config['smtp_crypto'], ['', 'tls', 'ssl'], true)) {
            throw new MailConfigurationException();
        }
        $port = filter_var($config['smtp_port'], FILTER_VALIDATE_INT);
        if ($port === false || $port < 1 || $port > 65535) throw new MailConfigurationException();
        $config['smtp_port'] = $port;
        $host = $config['smtp_host'];
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) throw new MailConfigurationException();
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $host = inet_ntop(inet_pton($host));
        }
        if ($host !== '' && filter_var($host, FILTER_VALIDATE_IP) === false
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new MailConfigurationException();
        }
        $config['smtp_host'] = $host;
        if (($config['email_from'] !== '' && filter_var($config['email_from'], FILTER_VALIDATE_EMAIL) === false)
            || preg_match('/[\x00-\x1F\x7F]/', $config['email_from_name'])) {
            throw new MailConfigurationException();
        }
        return $config;
    }

    /** The fingerprint hashes these exact transport preferences. */
    public function transportConfig(array $settings): array
    {
        $mail = $this->normalize($settings);
        if ($mail['email_from'] === '' || ($mail['email_protocol'] === 'smtp' && $mail['smtp_host'] === '')) {
            throw new MailConfigurationException('email_not_configured');
        }
        $config = ['protocol' => $mail['email_protocol'], 'mailType' => 'html', 'charset' => 'utf-8',
            'newline' => "\r\n", 'CRLF' => "\r\n", 'fromEmail' => $mail['email_from'], 'fromName' => $mail['email_from_name']];
        if ($mail['email_protocol'] === 'smtp') {
            $host = filter_var($mail['smtp_host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
                ? '[' . $mail['smtp_host'] . ']' : $mail['smtp_host'];
            $config += ['SMTPHost' => $host, 'SMTPPort' => $mail['smtp_port'],
                'SMTPUser' => $mail['smtp_user'], 'SMTPPass' => $mail['smtp_pass'], 'SMTPCrypto' => $mail['smtp_crypto']];
        } elseif ($mail['email_protocol'] === 'sendmail') {
            // CodeIgniter invokes popen with a command string. Only an operator-
            // owned executable reaches it, quoted as a single program name.
            $config['mailPath'] = escapeshellarg((new SendmailPolicy())->resolve($settings));
        }
        return $config;
    }
}
