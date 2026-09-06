<?php

namespace App\Commands;

use App\Models\UserModel;
use App\Services\SecretReader;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

class AdminResetPassword extends BaseCommand
{
    protected $group = 'eXtplorer';
    protected $name = 'admin:reset-password';
    protected $description = 'Resets an administrator password without exposing it in process arguments.';
    protected $usage = 'admin:reset-password [username] --password-file /run/secrets/admin';
    protected $arguments = [
        'username' => 'Administrator username',
    ];
    protected $options = [
        '--password-file' => 'Readable file containing the new password',
        '--password-stdin' => 'Read the new password from stdin',
    ];

    public function run(array $params)
    {
        try {
            $username = $params[0] ?? CLI::getOption('username') ?? null;
            if (!is_string($username) || trim($username) === '') {
                throw new RuntimeException('Administrator username is required.');
            }
            $username = trim($username);

            $passwordFile = CLI::getOption('password-file');
            if ($passwordFile === null) {
                foreach (CLI::getOptions() as $key => $value) {
                    if (str_starts_with($key, 'password-file=')) {
                        $passwordFile = substr($key, strlen('password-file='));
                        break;
                    }
                }
            }
            if (is_string($passwordFile) && trim($passwordFile) !== '') {
                $password = SecretReader::file(trim($passwordFile));
            } elseif (CLI::getOption('password-stdin') !== null) {
                $password = SecretReader::stdin();
            } else {
                throw new RuntimeException('Use --password-file or --password-stdin.');
            }

            $model = new UserModel();
            $user = $model->getUser($username);
            if ($user === null || ($user['role'] ?? '') !== 'admin') {
                throw new RuntimeException("Administrator account not found: {$username}");
            }
            if (!$model->changePassword($username, $password)) {
                throw new RuntimeException("Unable to reset administrator password: {$username}");
            }

            CLI::write("Administrator password reset for '{$username}'.", 'green');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());
            return EXIT_ERROR;
        }
    }
}
