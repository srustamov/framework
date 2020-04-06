<?php

namespace TT\Cache\Adapter;

use TT\Redis;

class RedisStore implements CacheStoreInterface
{
    private $key;

    private $put;

    private $expires;

    private $redis;

    /**
     * RedisStore constructor.
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }


    public function put(String $key, $value, $expires = null, $forever = false)
    {
        $this->put = true;

        $this->key = $key;

        if ($expires === null) {
            $expires = $this->expires;
        }

        if ($expires === null) {
            $this->redis->set($key, $value);
        } else {
            $this->redis->setex($key, $expires, $value);
        }

        return $this;
    }

    public function forever(String $key, $value)
    {
        return $this->day(30)->put($key, $value);
    }

    public function has($key)
    {
        return $this->redis->exists($key);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function forget($key)
    {
        $this->redis->del($key);
    }

    public function expires(Int $expires)
    {
        if ($this->put !== null) {
            $this->redis->expire($this->key, $expires);
        } else {
            $this->expires = $expires;
        }

        return $this;
    }


    public function minutes(Int $minutes)
    {
        return $this->expires($minutes * 60);
    }


    public function hours(Int $hours)
    {
        return $this->expires($hours * 3600);
    }


    public function day(Int $day)
    {
        return $this->expires($day * 3600 * 24);
    }


    public function flush()
    {
        $this->redis->flushAll();
    }

    public function __get($key)
    {
        return $this->redis->get($key);
    }


    public function __call($method, $args)
    {
        return $this->redis->$method(...$args);
    }

    public function close()
    {
        $this->redis->close();
    }
}
