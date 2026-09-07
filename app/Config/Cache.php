<?php

namespace Config;

use App\Services\SecretReader;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

class Cache extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Primary Handler
     * --------------------------------------------------------------------------
     *
     * The name of the preferred handler that should be used. If for some reason
     * it is not available, the $backupHandler will be used in its place.
     */
    public string $handler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Backup Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used in case the first one is
     * unreachable. Often, 'file' is used here since the filesystem is
     * always available, though that's not always practical for the app.
     */
    public string $backupHandler = 'dummy';

    /**
     * --------------------------------------------------------------------------
     * Key Prefix
     * --------------------------------------------------------------------------
     *
     * This string is added to all cache item names to help avoid collisions
     * if you run multiple applications with the same cache engine.
     */
    public string $prefix = '';

    /**
     * --------------------------------------------------------------------------
     * Default TTL
     * --------------------------------------------------------------------------
     *
     * The default number of seconds to save items when none is specified.
     *
     * WARNING: This is not used by framework handlers where 60 seconds is
     * hard-coded, but may be useful to projects and modules. This will replace
     * the hard-coded value in a future release.
     */
    public int $ttl = 60;

    /**
     * --------------------------------------------------------------------------
     * Reserved Characters
     * --------------------------------------------------------------------------
     *
     * A string of reserved characters that will not be allowed in keys or tags.
     * Strings that violate this restriction will cause handlers to throw.
     * Default: {}()/\@:
     *
     * NOTE: The default set is required for PSR-6 compliance.
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * --------------------------------------------------------------------------
     * File settings
     * --------------------------------------------------------------------------
     *
     * Your file storage preferences can be specified below, if you are using
     * the File driver.
     *
     * @var array{storePath?: string, mode?: int}
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    /**
     * -------------------------------------------------------------------------
     * Memcached settings
     * -------------------------------------------------------------------------
     *
     * Your Memcached servers can be specified below, if you are using
     * the Memcached drivers.
     *
     * @see https://codeigniter.com/user_guide/libraries/caching.html#memcached
     *
     * @var array{host?: string, port?: int, weight?: int, raw?: bool}
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    /**
     * -------------------------------------------------------------------------
     * Redis settings
     * -------------------------------------------------------------------------
     *
     * Your Redis server can be specified below, if you are using
     * the Redis or Predis drivers.
     *
     * @var array{host?: string, password?: string|null, port?: int, timeout?: int, database?: int}
     */
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    /**
     * --------------------------------------------------------------------------
     * Available Cache Handlers
     * --------------------------------------------------------------------------
     *
     * This is an array of cache engine alias' and class names. Only engines
     * that are listed here are allowed to be used.
     *
     * @var array<string, class-string<CacheInterface>>
     */
    public array $validHandlers = [
        'dummy'     => DummyHandler::class,
        'file'      => FileHandler::class,
        'memcached' => MemcachedHandler::class,
        'predis'    => PredisHandler::class,
        'redis'     => RedisHandler::class,
        'wincache'  => WincacheHandler::class,
    ];

    /**
     * --------------------------------------------------------------------------
     * Web Page Caching: Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Whether to take the URL query string into consideration when generating
     * output cache files. Valid options are:
     *
     *    false = Disabled
     *    true  = Enabled, take all query parameters into account.
     *            Please be aware that this may result in numerous cache
     *            files generated for the same page over and over again.
     *    ['q'] = Enabled, but only take into account the specified list
     *            of query parameters.
     *
     * @var bool|list<string>
     */
    public $cacheQueryString = false;

    public function __construct()
    {
        parent::__construct();

        $driver = strtolower(trim((string)(getenv('EXTPLORER_CACHE_DRIVER') ?: 'file')));
        $this->handler = match ($driver) {
            'file' => 'file',
            'redis' => 'redis',
            'dummy' => 'dummy',
            default => throw new \RuntimeException('EXTPLORER_CACHE_DRIVER must be file, redis or dummy.'),
        };
        $this->backupHandler = $this->handler === 'redis' ? 'dummy' : $this->handler;
        $prefix = trim((string)(getenv('EXTPLORER_CACHE_PREFIX') ?: 'extplorer_'));
        if ($prefix !== '' && preg_match('/[^A-Za-z0-9_-]/', $prefix)) {
            throw new \RuntimeException('EXTPLORER_CACHE_PREFIX may contain only letters, numbers, underscores and hyphens.');
        }
        $this->prefix = $prefix;
        $this->file['storePath'] = (string)(getenv('EXTPLORER_CACHE_PATH') ?: WRITEPATH . 'cache/');
        if ($this->handler === 'file' && !is_dir($this->file['storePath'])) {
            if (!mkdir($this->file['storePath'], 0775, true) && !is_dir($this->file['storePath'])) {
                throw new \RuntimeException('Unable to create the configured cache directory.');
            }
        }

        $passwordFile = getenv('EXTPLORER_REDIS_PASSWORD_FILE');
        $redisPassword = $passwordFile !== false && trim($passwordFile) !== ''
            ? SecretReader::file(trim($passwordFile))
            : (getenv('EXTPLORER_REDIS_PASSWORD') ?: null);
        $this->redis = array_replace($this->redis, [
            'host' => getenv('EXTPLORER_REDIS_HOST') ?: $this->redis['host'],
            'port' => (int)(getenv('EXTPLORER_REDIS_PORT') ?: $this->redis['port']),
            'password' => $redisPassword,
            'database' => (int)(getenv('EXTPLORER_REDIS_DATABASE') ?: $this->redis['database']),
            'timeout' => (int)(getenv('EXTPLORER_REDIS_TIMEOUT') ?: $this->redis['timeout']),
        ]);
    }
}
