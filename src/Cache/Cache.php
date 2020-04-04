<?php namespace TT\Cache;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Cache
 */


use TT\Engine\App;

class Cache
{
    /**@var Adapter\CacheStoreInterface*/
    private $adapter;


    public function __construct()
    {
        $adapter = App::get('config')->get('cache.adapter', 'file');

        $this->adapter($adapter);
    }



    public function adapter($adapter)
    {
        if ($adapter instanceof Adapter\CacheStoreInterface) {
            $this->adapter = $adapter;
        } elseif (is_string($adapter)) {
            switch (strtolower($adapter)) {
                case 'file':
                    $this->adapter = new Adapter\FileStore();
                    break;
                case 'memcache':
                    $this->adapter = new Adapter\MemcacheStore();
                    break;
                case 'redis':
                    $this->adapter = new Adapter\RedisStore();
                    break;
                case 'databse':
                    $this->adapter = new Adapter\DatabaseStore();
                    break;
                default:
                    throw new \RuntimeException('Cache adapter not found');
                    break;
            }
        } else {
            throw new \RuntimeException('Cache adapter type inappropriate');
        }
        return $this;
    }


    public function put(String $key, $value, $expires = null)
    {
        return $this->adapter->put($key, $value, $expires);
    }


    public function forever(String $key, $value)
    {
        return $this->adapter->forever($key, $value);
    }


    public function has($key)
    {
        return $this->adapter->has($key);
    }


    public function get($key)
    {
        return $this->adapter->get($key);
    }


    public function forget($key)
    {
        return $this->adapter->forget($key);
    }


    public function expires(Int $expires)
    {
        return $this->adapter->expires($expires);
    }


    public function minutes(Int $minutes)
    {
        return $this->adapter->minutes($minutes);
    }

    public function hours(Int $hours)
    {
        return $this->adapter->hours($hours);
    }

    public function day(Int $day)
    {
        return $this->adapter->day($day);
    }

    public function flush()
    {
        $this->adapter->flush();
    }

    public function __call($method, $args)
    {
        return $this->adapter->$method(...$args);
    }


    public function __destruct()
    {
        $this->adapter->close();
    }
}
