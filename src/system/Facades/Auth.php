<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 *
 * @method static guard(string $guard): bool
 * @method static guest(): bool
 * @method static check(): bool
 * @method static attempt(array $credentials, $remember = false,$once = false): bool
 * @method static once(array $credentials): bool
 * @method static logoutUser()
 * @method static getMessage()
 * @method static user($user = null,string $guard = null)
 * @method static setRemember($user)
 */



class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'authentication';
    }
}
