<?php namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


//-------------------------------------------------------------
// Base Controller Class
//-------------------------------------------------------------

use Exception;
use TT\Engine\App;
use TT\Libraries\View\View;

abstract class Controller
{

    protected $middleware_aliases = [];

    /**
     * @param String $file
     * @param array $data
     * @return View
     * @throws Exception
     */
    protected function view(String $file, array $data = []): View
    {
        return App::get('view')->render($file, $data);
    }


    /**
     * @param $middleware
     * @throws Exception
     */
    protected function middleware($middleware): void
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];

        if(empty($this->middleware_aliases)) {
            $this->middleware_aliases = App::get('route')->getMiddlewareAliases();
        }

        
        foreach ($middleware as $extension) {
            [$name, $excepts, $guard] = Middleware::getExceptsAndGuard($extension);
            if (isset($this->middleware_aliases[$name])) {
                Middleware::init($this->middleware_aliases[$name], $guard, $excepts);
            }
        }
    }


    /**
     * @param String $action
     * @param array $args
     * @param string $namespace
     * @return mixed
     */
    protected function callAction(String $action, array $args = [], $namespace = 'App\\Controllers')
    {
        if (strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action);

            $controller = '\\'.$namespace.'\\'.str_replace('/', '\\', $controller);

            return call_user_func_array([new $controller,$method], $args);
        }

        return call_user_func_array([$this,$action], $args);
    }
}
