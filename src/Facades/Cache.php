<?php namespace TT\Facades;


/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 *
 * @method static guard(string $guard): bool
 * @method static adapter($adapter)
 * @method static put(String $key, $value, $expires = null)
 * @method static forever(String $key, $value)
 * @method static has($key)
 * @method static get($key)
 * @method static forget($key)
 * @method static expires(Int $expires)
 * @method static minutes(Int $minutes)
 * @method static hours(Int $hours)
 * @method static day(Int $day)
 * @method static flush()
 */

class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
