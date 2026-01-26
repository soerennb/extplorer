<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Services\MountService;
use App\Services\LogService;

class MountController extends BaseController
{
    use ResponseTrait;

    private MountService $mountService;

    public function __construct()
    {
        $this->mountService = new MountService();
    }

    public function index()
    {
        $username = session('username');
        $mounts = $this->mountService->getUserMounts($username);
        return $this->respond(array_values($mounts));
    }

    public function create()
    {
        $json = $this->request->getJSON();
        $name = $json->name ?? '';
        $type = $json->type ?? 'local';
        $config = $json->config ?? [];

        if (!$name) return $this->fail('Name required');

        try {
            $id = $this->mountService->addMount(session('username'), $name, $type, (array)$config);
            LogService::log('Add Mount', $name, "Type: $type");
            return $this->respond(['status' => 'success', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (!$id) return $this->fail('ID required');

        try {
            $this->mountService->removeMount($id, session('username'));
            LogService::log('Remove Mount', $id);
            return $this->respond(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
