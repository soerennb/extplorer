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
        
        // --- Poor Man's Cron (Auto Cleanup) ---
        // Run once per hour on a random request
        $cronFile = WRITEPATH . 'last_cleanup.txt';
        $now = time();
        $lastRun = file_exists($cronFile) ? (int)file_get_contents($cronFile) : 0;
        
        // 3600 seconds = 1 hour
        if ($now - $lastRun > 3600) {
            // Update timestamp first to prevent race conditions (simple lock)
            file_put_contents($cronFile, $now);
            
            // Run logic (silently catch errors)
            try {
                $service = new \App\Services\ShareService();
                $service->processCleanup();
            } catch (\Exception $e) {
                log_message('error', 'Auto Cleanup Failed: ' . $e->getMessage());
            }
        }
    }
}
