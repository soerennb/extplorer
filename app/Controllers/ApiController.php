<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\VFS\LocalAdapter;
use Exception;

class ApiController extends BaseController
{
    use ResponseTrait;

    private LocalAdapter $fs;

    public function __construct()
    {
        // Ensure root exists
        $baseRoot = WRITEPATH . 'file_manager_root';
        if (!is_dir($baseRoot)) {
            mkdir($baseRoot, 0777, true);
        }

        $userHome = session('home_dir') ?: '/';
        
        // Sanitize userHome to prevent traversal
        $userHome = str_replace('..', '', $userHome);
        $userHome = trim($userHome, '/\\');

        $root = $baseRoot;
        if ($userHome) {
            $root .= DIRECTORY_SEPARATOR . $userHome;
        }

        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }

        $this->fs = new LocalAdapter($root);
    }

    public function ls()
    {
        $path = $this->request->getGet('path') ?? '';
        try {
            $data = $this->fs->listDirectory($path);
            return $this->respond($data);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function content()
    {
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');
        try {
            $content = $this->fs->readFile($path);
            return $this->respond(['content' => $content]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function save()
    {
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $content = $json->content ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            $this->fs->writeFile($path, $content ?? '');
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function rm()
    {
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            $this->fs->delete($path);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mkdir()
    {
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            $this->fs->createDirectory($path);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mv()
    {
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            $this->fs->move($from, $to);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function cp()
    {
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            $this->fs->copy($from, $to);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function upload()
    {
        $path = $this->request->getPost('path') ?? '';
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->fail($file ? $file->getErrorString() : 'No file uploaded');
        }

        try {
            $targetDir = $this->fs->resolvePath($path);
            
            if (!is_dir($targetDir)) {
                return $this->fail("Target directory does not exist.");
            }
            
            $file->move($targetDir, $file->getClientName());
            
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function download()
    {
        $path = $this->request->getGet('path');
        $inline = $this->request->getGet('inline');
        
        if (!$path) return $this->fail('Path required');

        try {
            $fullPath = $this->fs->resolvePath($path);
            
            if (is_dir($fullPath)) {
                // Folder Download -> Zip
                $zipName = basename($fullPath) . '.zip';
                $tempZip = WRITEPATH . 'cache/' . uniqid('dl_') . '.zip';
                
                // Use the archive method logic directly or via FS
                // Creating a one-off archive of this folder
                $this->fs->archive([$path], $tempZip); // $path is relative, fs->archive resolves it
                
                return $this->response->download($tempZip, null)->setFileName($zipName);
                // Note: CI4 download() usually deletes the file if third param is true? No, setFileName just sets header.
                // We need to delete the temp file after send. 
                // CI4 doesn't have native "delete after send" in download().
                // We can register a shutdown function or use readfile() and unlink().
                // Actually, let's try to output it directly.
                // Re-implementation:
                
                /*
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.$zipName.'"');
                header('Content-Length: ' . filesize($tempZip));
                readfile($tempZip);
                unlink($tempZip);
                exit; 
                */
                // But to be clean with CI4:
                // Let's rely on garbage collection for cache dir or just leave it for now? 
                // Better: Use a dedicated method.
                
                // Let's use the raw header approach for this special case to ensure unlink.
                $this->response->setHeader('Content-Type', 'application/zip')
                               ->setHeader('Content-Disposition', 'attachment; filename="'.$zipName.'"')
                               ->setBody(file_get_contents($tempZip));
                               
                unlink($tempZip);
                return $this->response;
            }

            if (!is_file($fullPath)) {
                return $this->failNotFound('File not found');
            }

            return $this->response->download($fullPath, null, (bool)$inline);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function thumb()
    {
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $fullPath = $this->fs->resolvePath($path);
            if (!is_file($fullPath)) return $this->failNotFound();

            // Cache file path (hash of full path + mtime to invalidate on change)
            $cacheName = md5($fullPath . filemtime($fullPath)) . '.jpg';
            $cacheDir = WRITEPATH . 'cache/thumbs';
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
            $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cacheName;

            // Generate if not exists
            if (!file_exists($cachePath)) {
                $image = \Config\Services::image();
                try {
                    $image->withFile($fullPath)
                          ->fit(100, 100, 'center')
                          ->save($cachePath, 80);
                } catch (\CodeIgniter\Images\Exceptions\ImageException $e) {
                    // If not an image or processing fails, return a default placeholder or 404
                    // For simplicity, let's just fail, frontend will handle broken img
                    return $this->fail('Not an image');
                }
            }

            // Serve
            $this->response->setHeader('Content-Type', 'image/jpeg');
            $this->response->setBody(file_get_contents($cachePath));
            return $this->response;

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function archive()
    {
        $json = $this->request->getJSON();
        $paths = $json->paths ?? [];
        $name = $json->name ?? 'archive.zip';
        $cwd = $json->cwd ?? '';

        if (empty($paths)) return $this->fail('No files selected');

        try {
            // Prepend CWD to paths if necessary, assuming paths are relative to CWD
            // Actually, let's assume the frontend sends full relative paths from root, 
            // OR relative to CWD. Let's enforce relative to root for clarity in API,
            // but store.js currently handles CWD.
            // Let's assume `paths` are full relative paths (e.g. "folder/file.txt")
            
            $destination = ($cwd ? $cwd . '/' : '') . $name;
            
            $this->fs->archive($paths, $destination);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function extract()
    {
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $cwd = $json->cwd ?? '';

        if (!$path) return $this->fail('Path required');

        try {
            // Extract to current folder
            $this->fs->extract($path, $cwd ?: '/');
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function chmod()
    {
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $mode = $json->mode ?? null; // Expecting octal string e.g., "755" or integer

        if (!$path || !$mode) return $this->fail('Path and Mode required');

        try {
            // Convert mode to octal integer if it's a string
            $octalMode = intval(strval($mode), 8);
            
            $this->fs->chmod($path, $octalMode);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
