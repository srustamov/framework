<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 *
 * @method static from($email, $name = null)
 * @method static to($email, $name = null)
 * @method static subject($subject)
 * @method static message($message)
 */
class Email extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'email';
    }
}
