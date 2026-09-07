<?php

namespace App\Controllers;

use App\Services\MountService;
use App\Services\LogService;
use App\Services\StepUpAuthenticationService;

class MountController extends BaseController
{
    use ApiResponseTrait;

    private MountService $mountService;

    public function __construct()
    {
        $this->mountService = new MountService();
    }

    public function index()
    {
        $username = session('username');
        $mounts = $this->mountService->getUserMounts($username, false);
        return $this->respond(array_values($mounts));
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('ID required');
        }

        try {
            $mount = $this->mountService->getMountForUser($id, session('username'), false);
            return $this->respond($mount);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function health($id = null)
    {
        if (!$id) return $this->fail('ID required');
        try {
            return $this->respond($this->mountService->getMountHealth($id, (string)session('username')));
        } catch (\Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function create()
    {
        if (($stepUp = $this->requireStepUp('mount.create')) !== true) return $stepUp;
        $json = $this->request->getJSON();
        $name = $json->name ?? '';
        $type = $json->type ?? 'local';
        $config = $json->config ?? [];

        if (!is_string($name) || $name === '' || !is_string($type) || (!is_array($config) && !is_object($config))) {
            return $this->fail('Invalid mount request');
        }

        try {
            $id = $this->mountService->addMount(session('username'), $name, $type, (array)$config);
            LogService::log('Add Mount', $name, "Type: $type");
            return $this->respond(['status' => 'success', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function update($id = null)
    {
        if (($stepUp = $this->requireStepUp('mount.update')) !== true) return $stepUp;
        if (!$id) {
            return $this->fail('ID required');
        }

        $json = $this->request->getJSON();
        $name = $json->name ?? '';
        $type = $json->type ?? 'local';
        $config = $json->config ?? [];

        if (!is_string($name) || $name === '' || !is_string($type) || (!is_array($config) && !is_object($config))) {
            return $this->fail('Invalid mount request');
        }

        try {
            $mount = $this->mountService->updateMount($id, session('username'), $name, $type, (array)$config);
            LogService::log('Update Mount', $name, "Type: $type");
            return $this->respond(['status' => 'success', 'mount' => $mount]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function test()
    {
        if (($stepUp = $this->requireStepUp('mount.test')) !== true) return $stepUp;
        $json = $this->request->getJSON();
        $id = $json->id ?? null;
        $name = $json->name ?? '';
        $type = $json->type ?? 'local';
        $config = $json->config ?? [];

        if (!is_string($name) || $name === '' || !is_string($type) || (!is_array($config) && !is_object($config))) {
            return $this->fail('Invalid mount request');
        }

        try {
            $result = $this->mountService->testMount(session('username'), $id, $name, $type, (array)$config);
            return $this->respond($result);
        } catch (\Exception $e) {
            if ($id !== null) {
                $this->mountService->recordMountHealth($id, (string)session('username'), false, $e->getMessage());
            }
            return $this->fail($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (($stepUp = $this->requireStepUp('mount.delete')) !== true) return $stepUp;
        if (!$id) return $this->fail('ID required');

        try {
            $this->mountService->removeMount($id, session('username'));
            LogService::log('Remove Mount', $id);
            return $this->respond(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function requireStepUp(string $action)
    {
        if ((new StepUpAuthenticationService())->consume($this->request, $action)) {
            return true;
        }

        return $this->fail([
            'error' => 'Additional authentication is required.',
            'action' => $action,
        ], 428, 'step_up_required');
    }
}
