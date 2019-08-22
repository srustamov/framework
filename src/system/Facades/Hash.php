<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @method static make($password)
 * @method static check($password, $password1)
 */



class Hash extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
