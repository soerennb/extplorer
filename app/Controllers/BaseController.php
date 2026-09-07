<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
        helper('auth');

        // Set a conservative Permissions-Policy header and avoid unsupported features.
        $this->response->setHeader(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), fullscreen=(self)'
        );
        $this->response
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (ENVIRONMENT === 'production' && $request->isSecure()) {
            $maxAge = (int)(getenv('EXTPLORER_HSTS_MAX_AGE') ?: 31536000);
            if ($maxAge < 0 || $maxAge > 63072000) {
                $maxAge = 31536000;
            }
            $hsts = 'max-age=' . $maxAge;
            if (filter_var(getenv('EXTPLORER_HSTS_INCLUDE_SUBDOMAINS'), FILTER_VALIDATE_BOOLEAN)) {
                $hsts .= '; includeSubDomains';
            }
            $this->response->setHeader('Strict-Transport-Security', $hsts);
        }
    }
}
