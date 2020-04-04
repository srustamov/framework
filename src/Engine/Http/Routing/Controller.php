<?php

namespace TT\Engine\Http\Routing;

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

    private $middleware = [];

    /**
     * @throws \Exception
     */
    protected function middleware($middleware)
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];

        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }


    final public function getMiddleware()
    {
        return $this->middleware;
    }



    final public function callAction(string $action, array $args = [])
    {
        return call_user_func_array([$this, $action], $args);
    }
}
