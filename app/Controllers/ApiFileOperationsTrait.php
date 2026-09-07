<?php

namespace App\Controllers;

use App\Services\LogService;
use App\Services\ResourcePolicy;
use Exception;

trait ApiFileOperationsTrait
{
    public function ls()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path') ?? '';
        $showHidden = $this->request->getGet('showHidden') === 'true';
        $limit = (int) ($this->request->getGet('limit') ?? 0);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $sortBy = $this->request->getGet('sortBy') ?? 'name';
        $sortDesc = $this->request->getGet('sortDesc') === 'true';
        $allowedSorts = ['name', 'size', 'mtime'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        try {
            $data = $this->fs->listDirectory($path, $showHidden);
            usort($data, function ($a, $b) use ($sortBy, $sortDesc) {
                if (($a['type'] ?? '') !== ($b['type'] ?? '')) {
                    return ($a['type'] ?? '') === 'dir' ? -1 : 1;
                }
                $valA = $a[$sortBy] ?? 0;
                $valB = $b[$sortBy] ?? 0;
                if ($sortBy === 'name') {
                    $cmp = strnatcasecmp((string) $valA, (string) $valB);
                } else {
                    $cmp = ($valA <=> $valB);
                }
                return $sortDesc ? -$cmp : $cmp;
            });
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
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function content()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');
        try {
            $metadata = $this->fs->getMetadata($path);
            if (is_array($metadata) && ($metadata['type'] ?? '') === 'file'
                && (int)($metadata['size'] ?? 0) > (new ResourcePolicy())->maxDownloadBytes()) {
                return $this->fail('File exceeds the configured download size limit.', 413);
            }
            $content = $this->fs->readFile($path);
            return $this->respond(['content' => $content]);
        } catch (\Throwable $e) {
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
            // Versioning: Backup existing file before saving
            $username = session('username');
            $fullPath = $this->fs->resolvePath($path);
            $versionService = new \App\Services\VersionService($username);
            $versionService->createVersion($fullPath, $path);

            if (!$this->fs->writeFile($path, (string)($content ?? ''))) {
                throw new Exception('Unable to save file.');
            }
            LogService::log('Save File', $path);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function versionList()
    {
        if (!can('read')) return $this->failForbidden();
        $path = $this->request->getGet('path');
        if (!$path) return $this->fail('Path required');

        try {
            $username = session('username');
            $versionService = new \App\Services\VersionService($username);
            return $this->respond(['versions' => $versionService->listVersions($path)]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function versionRestore()
    {
        if (!can('write')) return $this->failForbidden();
        $json = $this->request->getJSON();
        $path = $json->path ?? null;
        $versionId = $json->version_id ?? null;

        if (!$path || !$versionId) return $this->fail('Path and Version ID required');

        try {
            $username = session('username');
            $versionService = new \App\Services\VersionService($username);
            $versionService->restoreVersion($path, $versionId, $this->fs);
            LogService::log('Restore Version', $path, 'Version: ' . $versionId);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
            if (!$this->fs->createDirectory($path)) {
                throw new Exception('Unable to create directory.');
            }
            LogService::log('Create Directory', $path);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
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
            if (!$this->fs->move($from, $to)) {
                throw new Exception('Unable to move item.');
            }
            LogService::log('Move', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
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
            if (!$this->fs->copy($from, $to)) {
                throw new Exception('Unable to copy item.');
            }
            LogService::log('Copy', $from, 'To: ' . $to);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
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
                if ($path && !$this->fs->chown($path, $user, $group, $recursive)) {
                    throw new Exception('Unable to change ownership.');
                }
            }
            LogService::log('Chown' . ($recursive ? ' (Recursive)' : ''), implode(', ', $paths), 'User: ' . $user . ', Group: ' . $group);
            return $this->respond(['status' => 'success']);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    // --- Share API ---


}
