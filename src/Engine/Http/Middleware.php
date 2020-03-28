<?php  namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use Exception;
use RuntimeException;
use TT\Engine\App;

class Middleware
{

    /**
     * @param string|object $class
     * @param string $guard
     * @param array $excepts
     * @return bool|mixed
     * @throws Exception
     */

    public static function init($class, string $guard = 'default', $excepts = [])
    {
        $request  = App::get('request');

        foreach ($excepts as $action) {
            if ($request->controller(true) === strtolower($action)) {
                return true;
            }
        }

        $next = static function ($clientRequest) {
            if (App::isInstance($clientRequest, 'request')) {
                return App::get('response');
            }
        };

        $response = call_user_func([new $class(), 'handle'], $request, $next, $guard);

        if (!App::isInstance($response, 'response')) {
            App::get('response')->setContent($response)->send();
        }
    }


    /**
     * @param $extension
     * @return array
     */
    public static function getExceptsAndGuard($extension): array
    {
        $excepts = [];

        $guard = 'default';

        $name = $extension;

        if (strpos($extension, '|') !== false) {
            [$name, $excepts] = explode('|', $extension, 2);

            $excepts = explode(',', $excepts);
        }

        if (strpos($name, ':')) {
            [$name, $guard] = explode(':', $extension, 2);
        }


        return array($name,$excepts,$guard);
    }
}
