<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('app');
    }

    public function admin()
    {
        if (!can('admin_users') && !can('admin_settings')) {
            return redirect()->to(base_url());
        }

        return view('admin');
    }
}
