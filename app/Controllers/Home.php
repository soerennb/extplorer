<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $settingsService = new \App\Services\SettingsService();
        $data = [
            'webdavEnabled' => $settingsService->get('webdav_enabled', true)
        ];
        return view('app', $data);
    }

    public function admin()
    {
        if (!can('admin_users') && !can('admin_settings')) {
            return redirect()->to(base_url());
        }

        return view('admin');
    }
}
