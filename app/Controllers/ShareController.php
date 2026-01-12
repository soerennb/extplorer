<?php

namespace App\Controllers;

use App\Services\ShareService;
use App\Services\VFS\LocalAdapter;
use CodeIgniter\API\ResponseTrait;

class ShareController extends BaseController
{
    use ResponseTrait;

    public function index(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);

        if (!$share) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Link expired or invalid.");
        }

        // Password Protection Check
        if ($share['password_hash']) {
            // Check if verified in session
            $sessionKey = 'share_verified_' . $hash;
            if (!session($sessionKey)) {
                return view('shared_password', ['hash' => $hash]);
            }
        }

        // Serve Content
        $root = WRITEPATH . 'file_manager_root/' . $share['path'];
        if (!file_exists($root)) {
             throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Shared content missing.");
        }

        if (is_file($root)) {
            // Direct download/preview for single file share?
            // Usually showing a preview page is better UX
            return view('shared', [
                'share' => $share, 
                'is_file' => true,
                'filename' => basename($share['path']),
                'size' => filesize($root),
                'hash' => $hash
            ]);
        }

        // It's a directory
        // We need a file list. We can reuse the Vue app but strictly configured?
        // Or a simpler server-side rendered list for MVP? 
        // Let's go with a simpler Vue instance using the 'shared' layout.
        
        return view('shared', [
            'share' => $share, 
            'is_file' => false,
            'hash' => $hash
        ]);
    }

    public function auth(string $hash)
    {
        $service = new ShareService();
        $password = $this->request->getPost('password');

        if ($service->verifyPassword($hash, $password)) {
            session()->set('share_verified_' . $hash, true);
            return redirect()->to('/s/' . $hash);
        }

        return redirect()->back()->with('error', 'Invalid Password');
    }

    public function download(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if ($share['password_hash'] && !session('share_verified_' . $hash)) {
            return $this->failForbidden();
        }

        $fullPath = WRITEPATH . 'file_manager_root/' . $share['path'];
        $inline = $this->request->getGet('inline');
        
        // Handle sub-path request (e.g. /s/{hash}/download?path=subfolder/file.txt)
        $subPath = $this->request->getGet('path');
        if ($subPath) {
            // Sanitize
            $subPath = str_replace('..', '', $subPath);
            $fullPath .= '/' . $subPath;
        }

        if (!file_exists($fullPath)) return $this->failNotFound();

        if (is_dir($fullPath)) {
            // Zip directory
            $zipName = basename($fullPath) . '.zip';
            $tempZip = WRITEPATH . 'cache/' . uniqid('share_') . '.zip';
            $fs = new LocalAdapter(dirname($fullPath)); 
            $fs->archive([basename($fullPath)], $tempZip);
            
            $this->response->setHeader('Content-Type', 'application/zip')
                           ->setHeader('Content-Disposition', 'attachment; filename="'.$zipName.'"')
                           ->setBody(file_get_contents($tempZip));
            @unlink($tempZip);
            return $this->response;
        }

        if ($inline) {
            $mime = mime_content_type($fullPath);

            return $this->response
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
                ->setBody(file_get_contents($fullPath));
        }

        return $this->response->download($fullPath, null);
    }

    // JSON API for the shared view (listing subfolders)
    public function ls(string $hash)
    {
        $service = new ShareService();
        $share = $service->getShare($hash);
        if (!$share) return $this->failNotFound();

        // Password check
        if ($share['password_hash'] && !session('share_verified_' . $hash)) {
            return $this->failForbidden();
        }

        $root = WRITEPATH . 'file_manager_root/' . $share['path'];
        
        // Subpath
        $subPath = $this->request->getGet('path') ?? '';
        $subPath = str_replace('..', '', $subPath);
        
        // Create adapter jailed to the SHARE ROOT
        try {
            $fs = new LocalAdapter($root); 
            $items = $fs->listDirectory($subPath, false); // Don't show hidden
            return $this->respond(['items' => $items]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
