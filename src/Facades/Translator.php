<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @method static locale($locale = null)
 * @method static translate(string $string, array $array)
 */



class Translator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'translator';
    }
}
