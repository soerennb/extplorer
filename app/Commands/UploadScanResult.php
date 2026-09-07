<?php

namespace App\Commands;

use App\Services\UploadQuarantineService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

final class UploadScanResult extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'uploads:scan-result';
    protected $description = 'Finalizes an externally scanned upload quarantine item.';
    protected $usage = 'uploads:scan-result <id> <clean|infected|error|expired> [--reason text]';
    protected $arguments = [
        'id' => 'Quarantine item ID',
        'status' => 'Scan result',
    ];
    protected $options = ['--reason' => 'Optional scanner reason'];

    public function run(array $params)
    {
        try {
            $id = (string)($params[0] ?? '');
            $status = (string)($params[1] ?? '');
            if ($id === '' || $status === '') {
                throw new RuntimeException('Quarantine ID and scan result are required.');
            }
            $reason = (string)(CLI::getOption('reason') ?? '');
            $result = (new UploadQuarantineService())->markResult($id, $status, $reason);
            CLI::write(json_encode($result, JSON_THROW_ON_ERROR), 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error('Upload scan result failed: ' . $exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
