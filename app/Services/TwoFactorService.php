<?php

namespace App\Services;

use OTPHP\TOTP;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    /**
     * Generates a new random secret.
     */
    public function generateSecret(): string
    {
        return trim(TOTP::create()->getSecret());
    }

    /**
     * Generates a QR Code Data URI for the secret.
     */
    public function getQrCodeUrl(string $user, string $secret): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($user);
        $totp->setIssuer('eXtplorer');

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($totp->getProvisioningUri());

        return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
    }

    /**
     * Verifies a code against a secret.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        if (!$secret) return false;
        $totp = TOTP::create($secret);
        return $totp->verify($code);
    }

    /**
     * Generates a set of recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(5)); // 10 chars hex
        }
        return $codes;
    }
}
