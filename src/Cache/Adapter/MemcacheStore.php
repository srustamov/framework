<?php namespace TT\Cache\Adapter;

use TT\Engine\App;

class MemcacheStore implements CacheStoreInterface
{
    private $put = false;

    private $key;

    private $expires = 10;

    private $memcache;


    public function __construct()
    {
        $config = App::get('config')->get('cache.memcache');

        if (class_exists('\\Memcache')) {
            $this->memcache = new \Memcache;
        } elseif (class_exists('\\Memcached')) {
            $this->memcache = new \Memcached;
        } elseif (function_exists('memcache_connect')) {
            $this->memcache = memcache_connect($config['host'], $config['port']);
        } else {
            throw new \RuntimeException('Class Memcache (Memcached) not found');
        }

        if ($this->memcache) {
            $this->memcache->addServer($config['host'], $config['port']);
        }
    }


    /**
     * @param String $key
     * @param $value
     * @param null $expires
     * @param bool $forever
     * @return $this
     */
    public function put(String $key, $value, $expires = null, $forever = false)
    {
        if ($expires === null) {
            if ($this->expires === null) {
                $this->put = true;

                $this->key = $key;

                $this->memcache->set($key, $value, null, 10);
            } else {
                $this->memcache->set($key, $value, null, $this->expires);

                $this->expires = null;
            }
        } else {
            $this->memcache->set($key, $value, null, $expires);

            $this->expires = null;
        }

        return $this;
    }

    /**
     * @param String $key
     * @param $value
     * @return MemcacheStore
     */
    public function forever(String $key, $value)
    {
        return $this->day(30)->put($key, $value);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->memcache->get($key) ? true : false;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->memcache->get($key);
    }

    public function forget($key)
    {
        return $this->memcache->delete($key);
    }

    /**
     * @param Int $expires
     * @return $this
     */
    public function expires(Int $expires)
    {
        if ($this->put && $this->key !== null) {
            $this->memcache->set(
                $this->key,
                $this->memcache->get($this->key),
                null, $expires
            );

            $this->put = false;

            $this->key = null;
        } else {
            $this->expires = $expires;
        }

        return $this;
    }


    /**
     * @param Int $minutes
     * @return MemcacheStore
     */
    public function minutes(Int $minutes)
    {
        return $this->expires($minutes * 60);
    }


    /**
     * @param Int $hours
     * @return MemcacheStore
     */
    public function hours(Int $hours)
    {
        return $this->expires($hours * 3600);
    }


    /**
     * @param Int $day
     * @return MemcacheStore
     */
    public function day(Int $day)
    {
        return $this->expires($day * 3600 * 24);
    }

    /**
     * Flush MemCache cache store
     */
    public function flush()
    {
        $this->memcache->flush();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->memcache->get($key);
    }


    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->memcache->$method(...$args);
    }


    /**
     * Close memcache store connection
     */
    public function close()
    {
        $this->memcache->close();
    }
}
