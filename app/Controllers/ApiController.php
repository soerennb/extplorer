<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\VFS\LocalAdapter;
use App\Services\LogService;
use Exception;

class ApiController extends BaseController
{
    use ResponseTrait;

    private \App\Services\VFS\IFileSystem $fs;

    public function __construct()
    {
        $conn = session('connection') ?? ['mode' => 'local'];

        if ($conn['mode'] === 'ftp') {
            $this->fs = new \App\Services\VFS\FtpAdapter(
                $conn['host'],
                $conn['user'],
                $conn['pass'],
                $conn['port']
            );
        } else if ($conn['mode'] === 'sftp') {
            $this->fs = new \App\Services\VFS\Ssh2Adapter(
                $conn['host'],
                $conn['user'],
                $conn['pass'],
                $conn['port']
            );
        } else {
            // Ensure root exists
            $baseRoot = WRITEPATH . 'file_manager_root';
            if (!is_dir($baseRoot)) {
                mkdir($baseRoot, 0755, true);
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
                mkdir($root, 0755, true);
            }

            $this->fs = new LocalAdapter($root);
        }
    }

    public function ls()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path') ?? '';
        $showHidden = $this->request->getGet('showHidden') === 'true';
        $limit = (int) ($this->request->getGet('limit') ?? 0);
        $offset = (int) ($this->request->getGet('offset') ?? 0);

        try {
            $data = $this->fs->listDirectory($path, $showHidden);
            $total = count($data);

            if ($limit > 0) {
                $data = array_slice($data, $offset, $limit);
            }

            // Inject Share Status
            try {
                $username = session('username');
                $shareService = new \App\Services\ShareService();
                $shares = $shareService->listUserShares($username);
                $shareMap = [];
                foreach ($shares as $s) $shareMap[$s['path']] = true;

                foreach ($data as &$item) {
                    if (isset($shareMap[$item['path']])) $item['is_shared'] = true;
                }
            } catch (\Exception $e) {
                // Ignore share service errors to not break ls
            }

            return $this->respond([
                'items' => $data,
                'total' => $total
            ]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function content()
    {
        if (!can('read')) return $this->failForbidden();
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
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $content = $json->content ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            $this->fs->writeFile($path, $content ?? '');
            LogService::log('Save File', $path);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function rm()
    {
        if (!can('delete')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            // Use TrashService instead of direct delete
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            
            // Resolve full path to verify existence and for the move operation
            $fullPath = $this->fs->resolvePath($path);
            
            $trashService->moveToTrash($fullPath, $path);
            
            LogService::log('Move to Trash', $path);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashList()
    {
        if (!can('read')) return $this->failForbidden();
        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            return $this->respond(['items' => $trashService->listItems()]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashRestore()
    {
        if (!can('delete')) return $this->failForbidden(); // Restore implies write/delete permission
        $json = $this->request->getJSON();
        $id = $json->id ?? null;

        if (!$id) return $this->fail('ID required');

        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->restore($id, $this->fs);
            LogService::log('Restore from Trash', $id);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashDelete()
    {
        if (!can('delete')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $id = $json->id ?? null;

        if (!$id) return $this->fail('ID required');

        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->deletePermanently($id);
            LogService::log('Permanent Delete', $id);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function trashEmpty()
    {
        if (!can('delete')) return $this->failForbidden();
        try {
            $username = session('username');
            $trashService = new \App\Services\TrashService($username);
            $trashService->emptyTrash();
            LogService::log('Empty Trash', 'All items');
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mkdir()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;

        if (!$path) return $this->fail('Path required');

        try {
            $this->fs->createDirectory($path);
            LogService::log('Create Directory', $path);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function mv()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            $this->fs->move($from, $to);
            LogService::log('Move', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function cp()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $from = $json->from ?? null;
        $to = $json->to ?? null;

        if (!$from || !$to) return $this->fail('From and To required');

        try {
            $this->fs->copy($from, $to);
            LogService::log('Copy', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function isExtensionAllowed(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $allowed = session('allowed_extensions');
        $blocked = session('blocked_extensions');

        if ($allowed) {
            $allowedList = array_map('trim', explode(',', strtolower($allowed)));
            if (!in_array($ext, $allowedList)) {
                return false;
            }
        }

        if ($blocked) {
            $blockedList = array_map('trim', explode(',', strtolower($blocked)));
            if (in_array($ext, $blockedList)) {
                return false;
            }
        }

        // Hardened Default: Block dangerous extensions if not explicitly allowed
        if (empty($allowed)) {
            $dangerous = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'pl', 'py', 'rb', 'cgi', 'exe', 'sh', 'bat', 'cmd', 'htaccess', 'htpasswd'];
            if (in_array($ext, $dangerous)) {
                return false;
            }
        }

        return true;
    }

    public function upload()
    {
        if (!can('upload')) return $this->failForbidden();
        $path = $this->request->getPost('path') ?? '';
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->fail($file ? $file->getErrorString() : 'No file uploaded');
        }

        $name = $file->getClientName();
        if (!$this->isExtensionAllowed($name)) {
            return $this->fail("Uploading files with this extension is not allowed.");
        }

        try {
            $targetDir = $this->fs->resolvePath($path);
            
            if (!is_dir($targetDir)) {
                return $this->fail("Target directory does not exist.");
            }
            
            $file->move($targetDir, $name);
            LogService::log('Upload', $path, 'File: ' . $name);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function uploadChunk()
    {
        if (!can('upload')) return $this->failForbidden();
        
        $file = $this->request->getFile('file');
        $filename = $this->request->getPost('filename');
        $chunkIndex = (int)$this->request->getPost('chunkIndex');
        $totalChunks = (int)$this->request->getPost('totalChunks');
        $targetPath = $this->request->getPost('path') ?? '';

        if (!$file || !$file->isValid()) return $this->fail('Invalid chunk');

        if (!$this->isExtensionAllowed($filename)) {
            return $this->fail("Uploading files with this extension is not allowed.");
        }

        $tempDir = WRITEPATH . 'uploads/chunks/' . md5(session_id() . $filename);
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $file->move($tempDir, $chunkIndex . '.part');

        if ($chunkIndex === $totalChunks - 1) {
            // Last chunk, assemble
            try {
                $finalPath = $this->fs->resolvePath($targetPath) . DIRECTORY_SEPARATOR . $filename;
                $out = fopen($finalPath, 'wb');
                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = $tempDir . DIRECTORY_SEPARATOR . $i . '.part';
                    fwrite($out, file_get_contents($chunkPath));
                    unlink($chunkPath);
                }
                fclose($out);
                rmdir($tempDir);
                LogService::log('Upload (Chunked)', $targetPath, 'File: ' . $filename);
                return $this->respond(['status' => 'assembled']);
            } catch (Exception $e) {
                return $this->fail($e->getMessage());
            }
        }

        return $this->respond(['status' => 'chunk_saved', 'index' => $chunkIndex]);
    }

    public function download()
    {
        if (!can('read')) return $this->failForbidden();
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

            // Security: Only allow inline for safe image types
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $safeInlineTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if ($inline && !in_array($ext, $safeInlineTypes)) {
                $inline = false;
            }

            return $this->response->download($fullPath, null, (bool)$inline);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function thumb()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $fullPath = $this->fs->resolvePath($path);
            if (!is_file($fullPath)) return $this->failNotFound();

            // Cache file path (hash of full path + mtime to invalidate on change)
            $cacheName = md5($fullPath . filemtime($fullPath)) . '.jpg';
            $cacheDir = WRITEPATH . 'cache/thumbs';
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
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

    public function search()
    {
        if (!can('read')) return $this->failForbidden();
        $query = $this->request->getGet('q');
        if (!$query) return $this->fail('Query required');

        try {
            $results = $this->fs->search($query);
            return $this->respond([
                'items' => $results,
                'total' => count($results)
            ]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function dirsize()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $size = $this->fs->getDirectorySize($path);
            return $this->respond(['size' => $size]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function archive()
    {
        if (!can('archive')) return $this->failForbidden();
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
            LogService::log('Archive', $destination, 'Sources: ' . count($paths));
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function extract()
    {
        if (!can('extract')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $cwd = $json->cwd ?? '';

        if (!$path) return $this->fail('Path required');

        try {
            // Extract to current folder
            $this->fs->extract($path, $cwd ?: '/');
            LogService::log('Extract', $path, 'To: ' . ($cwd ?: '/'));
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

        public function chmod()

        {

            if (!can('chmod')) return $this->failForbidden();

            $json = $this->request->getJSON();

            $paths = $json->paths ?? [$json->path ?? null];

            $mode = $json->mode ?? null;

            $recursive = (bool)($json->recursive ?? false);

    

            if (empty(array_filter($paths)) || !$mode) return $this->fail('Paths and Mode required');

    

            try {

                $octalMode = intval(strval($mode), 8);

                foreach ($paths as $path) {

                    if ($path) $this->fs->chmod($path, $octalMode, $recursive);

                }

                LogService::log('Chmod' . ($recursive ? ' (Recursive)' : ''), implode(', ', $paths), 'Mode: ' . $mode);

                return $this->respond(['status' => 'success']);

            } catch (Exception $e) {

                return $this->fail($e->getMessage());

            }

        }

    public function chown()
    {
        if (!can('admin_users')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $paths = $json->paths ?? [$json->path ?? null];
        $user = $json->user ?? null;
        $group = $json->group ?? null;
        $recursive = (bool)($json->recursive ?? false);

        if (empty(array_filter($paths))) return $this->fail('Paths required');

        try {
            foreach ($paths as $path) {
                if ($path) $this->fs->chown($path, $user, $group, $recursive);
            }
            LogService::log('Chown' . ($recursive ? ' (Recursive)' : ''), implode(', ', $paths), 'User: ' . $user . ', Group: ' . $group);
            return $this->respond(['status' => 'success']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    // --- Share API ---

    public function shareCreate()
    {
        if (!can('read')) return $this->failForbidden();
        
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $password = $json->password ?? null;
        $expires = $json->expires ?? null; // Timestamp or ISO string? Let's assume timestamp from frontend
        
        if (!$path) return $this->fail('Path required');
        
        // Verify existence within user jail
        try {
            $this->fs->resolvePath($path); // Throws if invalid/traversal
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        try {
            $service = new \App\Services\ShareService();
            $share = $service->createShare($path, session('username'), $password, $expires);
            LogService::log('Create Share', $path);
            return $this->respond(['status' => 'success', 'share' => $share]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareDelete()
    {
        $json = $this->request->getJSON();
        $hash = $json->hash ?? null;
        if (!$hash) return $this->fail('Hash required');

        try {
            $service = new \App\Services\ShareService();
            $share = $service->getShare($hash);
            
            // Allow admin to delete any share, user only their own
            if ($share && ($share['created_by'] === session('username') || can('admin_users'))) {
                $service->deleteShare($hash);
                LogService::log('Delete Share', $hash);
                return $this->respond(['status' => 'success']);
            }
            return $this->failForbidden();
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function shareList()
    {
        try {
            $service = new \App\Services\ShareService();
            $shares = $service->listUserShares(session('username'));
            return $this->respond(['items' => $shares]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
