<?php

namespace TT\Cache;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Cache
 */


use TT\Engine\App;
use RuntimeException;

class Cache
{
    protected $adapters = [
        'file' => Adapter\FileStore::class,
        'redis' => Adapter\RedisStore::class,
        'memcache' => Adapter\MemcacheStore::class,
        'database' => Adapter\DatabaseStore::class,
    ];

    /**@var Adapter\CacheStoreInterface*/
    private $adapter;

    private $app;

    private $config;


    /**
     * Cache constructor.
     * @param array|null $config
     * @param App $app
     */
    public function __construct(App $app,array $config = null)
    {
        $this->app = $app;

        if (!$config) {
            $config = $this->app['config']->get('cache');
        }

        $this->config = $config;

        $this->adapter($this->config['adapter'] ?? 'file');
    }


    /**
     * @param $adapter
     * @return $this
     */
    public function adapter($adapter)
    {
        if ($adapter instanceof Adapter\CacheStoreInterface) {
            $this->adapter = $adapter;
        } elseif (is_string($adapter)) {
            if (array_key_exists($adapter, $this->adapters)) {
                $this->adapter = $this->app->make(
                    $this->adapters[$adapter],
                    $this->config[$adapter] ?? null
                );
            } else {
                throw new RuntimeException(sprintf(
                    'Cache adapter[%s] not found',
                    $adapter
                ));
            }
        } else {
            throw new RuntimeException('Cache adapter type inappropriate');
        }
        return $this;
    }


    /**
     * @param String $key
     * @param $value
     * @param null $expires
     * @return mixed
     */
    public function put(String $key, $value, $expires = null)
    {
        return $this->adapter->put($key, $value, $expires);
    }


    /**
     * @param String $key
     * @param $value
     * @return mixed
     */
    public function forever(String $key, $value)
    {
        return $this->adapter->forever($key, $value);
    }


    /**
     * @param $key
     * @return mixed
     */
    public function has($key)
    {
        return $this->adapter->has($key);
    }


    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->adapter->get($key);
    }


    /**
     * @param $key
     * @return mixed
     */
    public function forget($key)
    {
        return $this->adapter->forget($key);
    }


    /**
     * @param Int $expires
     * @return mixed
     */
    public function expires(Int $expires)
    {
        return $this->adapter->expires($expires);
    }


    /**
     * @param Int $minutes
     * @return mixed
     */
    public function minutes(Int $minutes)
    {
        return $this->adapter->minutes($minutes);
    }

    /**
     * @param Int $hours
     * @return mixed
     */
    public function hours(Int $hours)
    {
        return $this->adapter->hours($hours);
    }

    /**
     * @param Int $day
     * @return mixed
     */
    public function day(Int $day)
    {
        return $this->adapter->day($day);
    }

    /**
     *
     */
    public function flush()
    {
        $this->adapter->flush();
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->adapter->$method(...$args);
    }


    public function __destruct()
    {
        $this->adapter->close();
    }
}
