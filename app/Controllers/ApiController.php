<?php

namespace App\Controllers;

/**
 * Backwards-compatible API facade.
 *
 * Routes use the focused controllers below. Keeping this facade means plugins
 * and existing tests that referenced ApiController continue to work while
 * endpoint responsibilities remain separated by concern.
 */
class ApiController extends ApiBaseController
{
    use ApiFileOperationsTrait;
    use ApiTransferOperationsTrait;
    use ApiDownloadOperationsTrait;
    use ApiShareOperationsTrait;
}
