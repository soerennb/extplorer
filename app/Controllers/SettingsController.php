<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\SettingsService;
use App\Services\EmailService;
use App\Services\LogService;

class SettingsController extends BaseController
{
    use ResponseTrait;

    private SettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    private function checkAdmin()
    {
        if (!can('admin_users')) { // Re-using user admin permission for now
            return $this->failForbidden('Access denied');
        }
        return true;
    }

    public function index()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $settings = $this->settingsService->getSettings();
        
        // Mask password
        if (!empty($settings['smtp_pass'])) {
            $settings['smtp_pass'] = '********';
        }

        return $this->respond($settings);
    }

    public function update()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $json = $this->request->getJSON(true);
        if (!$json) return $this->fail('Invalid JSON');

        // If password is mask, don't update it (keep existing)
        if (isset($json['smtp_pass']) && $json['smtp_pass'] === '********') {
            unset($json['smtp_pass']);
        }

        $this->settingsService->saveSettings($json);
        LogService::log('Update Settings', 'System Settings Updated');
        
        return $this->respond(['status' => 'success']);
    }

    public function testEmail()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $email = $this->request->getJSON()->email ?? null;
        if (!$email) return $this->fail('Email required');

        // Temporarily save settings from request to test un-saved configs? 
        // Or strictly test saved settings?
        // Usually safer to test SAVED settings to ensure persistence works, 
        // but often users want to test BEFORE saving. 
        // Let's instantiate EmailService which loads from DISK.
        // So user must save first. 
        
        $svc = new EmailService();
        if ($svc->sendTestEmail($email)) {
             return $this->respond(['status' => 'success']);
        }

        return $this->fail('Failed to send email. Check logs. ' . strip_tags($svc->getDebugger()));
    }
}
