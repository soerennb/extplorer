<?php

namespace Config;

use App\Services\SecretReader;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Session\Handlers\RedisHandler;

class Session extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Session Driver
     * --------------------------------------------------------------------------
     *
     * The session storage driver to use:
     * - `CodeIgniter\Session\Handlers\FileHandler`
     * - `CodeIgniter\Session\Handlers\DatabaseHandler`
     * - `CodeIgniter\Session\Handlers\MemcachedHandler`
     * - `CodeIgniter\Session\Handlers\RedisHandler`
     *
     * @var class-string<BaseHandler>
     */
    public string $driver = FileHandler::class;

    /**
     * --------------------------------------------------------------------------
     * Session Cookie Name
     * --------------------------------------------------------------------------
     *
     * The session cookie name, must contain only [0-9a-z_-] characters
     */
    public string $cookieName = 'ci_session';

    /**
     * --------------------------------------------------------------------------
     * Session Expiration
     * --------------------------------------------------------------------------
     *
     * The number of SECONDS you want the session to last.
     * Setting to 0 (zero) means expire when the browser is closed.
     */
    public int $expiration = 7200;

    /**
     * --------------------------------------------------------------------------
     * Session Save Path
     * --------------------------------------------------------------------------
     *
     * The location to save sessions to and is driver dependent.
     *
     * For the 'files' driver, it's a path to a writable directory.
     * WARNING: Only absolute paths are supported!
     *
     * For the 'database' driver, it's a table name.
     * Please read up the manual for the format with other session drivers.
     *
     * IMPORTANT: You are REQUIRED to set a valid save path!
     */
    public string $savePath = WRITEPATH . 'session';

    /**
     * --------------------------------------------------------------------------
     * Session Match IP
     * --------------------------------------------------------------------------
     *
     * Whether to match the user's IP address when reading the session data.
     *
     * WARNING: If you're using the database driver, don't forget to update
     *          your session table's PRIMARY KEY when changing this setting.
     */
    public bool $matchIP = false;

    /**
     * --------------------------------------------------------------------------
     * Session Time to Update
     * --------------------------------------------------------------------------
     *
     * How many seconds between CI regenerating the session ID.
     */
    public int $timeToUpdate = 300;

    /**
     * --------------------------------------------------------------------------
     * Session Regenerate Destroy
     * --------------------------------------------------------------------------
     *
     * Whether to destroy session data associated with the old session ID
     * when auto-regenerating the session ID. When set to FALSE, the data
     * will be later deleted by the garbage collector.
     */
    public bool $regenerateDestroy = false;

    /**
     * --------------------------------------------------------------------------
     * Session Database Group
     * --------------------------------------------------------------------------
     *
     * DB Group for the database session.
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------------
     * Lock Retry Interval (microseconds)
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Time (microseconds) to wait if lock cannot be acquired.
     * The default is 100,000 microseconds (= 0.1 seconds).
     */
    public int $lockRetryInterval = 100_000;

    /**
     * --------------------------------------------------------------------------
     * Lock Max Retries
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Maximum number of lock acquisition attempts.
     * The default is 300 times. That is lock timeout is about 30 (0.1 * 300)
     * seconds.
     */
    public int $lockMaxRetries = 300;

    public function __construct()
    {
        parent::__construct();

        $driver = strtolower(trim((string)(getenv('EXTPLORER_SESSION_DRIVER') ?: 'file')));
        $this->driver = match ($driver) {
            'file' => FileHandler::class,
            'database' => DatabaseHandler::class,
            'redis' => RedisHandler::class,
            default => throw new \RuntimeException('EXTPLORER_SESSION_DRIVER must be file, database or redis.'),
        };

        if ($driver === 'database') {
            $this->savePath = (string)(getenv('EXTPLORER_SESSION_TABLE') ?: 'ci_sessions');
            $this->DBGroup = (string)(getenv('EXTPLORER_DB_GROUP') ?: 'default');
        } elseif ($driver === 'redis') {
            $url = (string)(getenv('EXTPLORER_REDIS_URL') ?: 'tcp://' . (getenv('EXTPLORER_REDIS_HOST') ?: '127.0.0.1') . ':' . (getenv('EXTPLORER_REDIS_PORT') ?: '6379'));
            $query = [];
            $passwordFile = getenv('EXTPLORER_REDIS_PASSWORD_FILE');
            $password = $passwordFile !== false && trim($passwordFile) !== ''
                ? SecretReader::file(trim($passwordFile))
                : (getenv('EXTPLORER_REDIS_PASSWORD') ?: null);
            if ($password !== null && !str_contains($url, 'auth=')) {
                $query['auth'] = $password;
            }
            if (getenv('EXTPLORER_REDIS_DATABASE') !== false && !str_contains($url, 'database=')) {
                $query['database'] = (int)getenv('EXTPLORER_REDIS_DATABASE');
            }
            if ($query !== []) {
                $this->savePath = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
            } else {
                $this->savePath = $url;
            }
        } else {
            $this->savePath = (string)(getenv('EXTPLORER_SESSION_PATH') ?: WRITEPATH . 'session');
        }
    }
}
