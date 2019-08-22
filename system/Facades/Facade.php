<?php namespace TT\Facades;

use Exception;
use RuntimeException;
use TT\Engine\App;

abstract class Facade
{
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }


    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($method, $args)
    {
        $instance = App::get(static::getFacadeAccessor());

        return $instance->$method(...$args);
    }
}
