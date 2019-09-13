<?php namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


//-------------------------------------------------------------
// Base Controller Class
//-------------------------------------------------------------

use TT\Engine\App;

abstract class Controller
{



    protected $middleware_aliases = [];


    /**
     * @param String $file
     * @param array $data
     * @return \TT\Libraries\View\View
     * @throws \Exception
     */
    protected function view(String $file, array $data = [])
    {
        return App::get('view')->render($file, $data);
    }


    /**
     * @throws \Exception
     */
    protected function middleware($middleware)
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];

        if(empty($this->middleware_aliases)) {
            $this->middleware_aliases = App::get('route')->getMiddlewareAliases();
        }

        
        foreach ($middleware as $extension) {
            list($name, $excepts, $guard) = Middleware::getExceptsAndGuard($extension);
            if (isset($this->middlewareAliases[$name])) {
                Middleware::init($this->middlewareAliases[$name], $guard, $excepts);
            }
        }
    }



    protected function callAction(String $action, array $args = [], $namespace = 'App\\Controllers')
    {
        if (strpos($action, '@') !== false) {
            list($controller, $method) = explode('@', $action);

            $controller = '\\'.$namespace.'\\'.str_replace('/', '\\', $controller);

            return call_user_func_array([new $controller,$method], $args);
        }

        return call_user_func_array([$this,$action], $args);
    }
}
