<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT

 * @method static replace_first($search, $replace, $subject)
 * @method static random($length = 6, $type = null):String
 * @method static replace_last($search, $replace, $subject)
 * @method static slug($str, $separator = '-'): String
 * @method static limit($value, $limit = 100, $end = '...'):String
 * @method static upper($str): String
 * @method static lower($str): String
 * @method static title($str): String
 * @method static len($value, $encoding = null)
 * @method static replace_array($search, array $replace, $subject): String
 * @method static fullTrim($str, $char = ' ')
 */

class Str extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'str';
    }
}
