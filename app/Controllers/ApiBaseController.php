<?php

namespace App\Controllers;

use App\Services\VFS\IFileSystem;
use App\Services\VFS\VfsFactory;

abstract class ApiBaseController extends BaseController
{
    use ApiResponseTrait;

    protected IFileSystem $fs;

    public function __construct()
    {
        $connection = session('connection') ?? ['mode' => 'local'];
        $this->fs = VfsFactory::createFileSystem(session('username'), $connection);
    }
}
