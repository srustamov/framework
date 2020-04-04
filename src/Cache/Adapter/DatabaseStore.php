<?php namespace TT\Cache\Adapter;

use TT\Engine\App;
use TT\Facades\DB;

class DatabaseStore implements CacheStoreInterface
{
    private $put = false;

    private $key;

    private $expires;

    private $table;


    public function __construct()
    {
        $this->table = App::get('config')
            ->get('cache.database', ['table' => 'cache'])['table'];

        $this->gc();
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
            if ($this->expires === null && !$forever) {
                $this->put = true;

                $this->key = $key;

                DB::pdo("REPLACE INTO $this->table SET cache_key= '$key',cache_value='$value'");
            } else {
                $expires = time() + $this->expires;

                DB::pdo("REPLACE INTO $this->table SET cache_key='$key',cache_value='$value', expires=" . ($forever ? 0 : $expires));

                $this->expires = null;
            }
        } else {
            DB::pdo("REPLACE INTO $this->table SET cache_key='$key',cache_value='$value' ,expires=$expires");

            $this->expires = null;
        }

        return $this;
    }

    /**
     * @param String $key
     * @param $value
     * @return DatabaseStore
     */
    public function forever(String $key, $value)
    {
        return $this->put($key, $value, null, true);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): Bool
    {
        return (bool)DB::table($this->table)->where('cache_key', $key)->first();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return DB::table($this->table)->where('cache_key', $key)->first();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function forget($key)
    {
        return DB::table($this->table)->where('cache_key', $key)->delete();
    }

    /**
     * @param Int $expires
     * @return $this
     */
    public function expires(Int $expires)
    {
        if ($this->put && $this->key !== null) {
            DB::table($this->table)
                ->where('cache_key', $this->key)
                ->update(['expires' => time() + $expires]);

            $this->put = false;

            $this->key = null;
        } else {
            $this->expires = $expires;
        }

        return $this;
    }


    /**
     * @param Int $minutes
     * @return DatabaseStore
     */
    public function minutes(Int $minutes)
    {
        return $this->expires($minutes * 60);
    }


    /**
     * @param Int $hours
     * @return DatabaseStore
     */
    public function hours(Int $hours)
    {
        return $this->expires($hours * 3600);
    }


    /**
     * @param Int $day
     * @return DatabaseStore
     */
    public function day(Int $day)
    {
        return $this->expires($day * 3600 * 24);
    }



    public function flush()
    {
        DB::table($this->table)->truncate();
    }

    /**
     *
     */
    private function gc()
    {
        DB::pdo('DELETE FROM ' .$this->table. ' WHERE expires < ' . time() . ' AND expires != 0');
    }


    public function __call($method, $args)
    {
        throw new \BadMethodCallException("Call to undefined method Cache::$method()");
    }


    public function close()
    {
        return true;
    }
}
