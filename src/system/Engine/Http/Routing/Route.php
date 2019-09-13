<?php

namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use Closure;
use Exception;
use TT\Engine\App;
use TT\Engine\Reflections;
use TT\Engine\Http\Middleware;
use TT\Exceptions\RouteException;
use App\Exceptions\NotFoundException;

class Route
{
    use Traits\RouteMethods;
    use Traits\RouteGroup;
    use Traits\Parse;

    public $routes = [
        'GET'     => [],
        'POST'    => [],
        'PUT'     => [],
        'DELETE'  => [],
        'OPTIONS' => [],
        'PATCH'   => [],
        'NAMES'   => [],
    ];


    private $middlewareAliases = [];

    private $patterns = [];

    private $domain;

    private $notFound = false;

    private $namespace = 'App\Controllers';

    private $prefix;

    private $group_name;

    private $name;

    private $middleware = [];

    private $pattern = [];

    private $methods = [];

    private $group_middleware = [];

    private $ajax = false;

    /**@var App*/
    private $app;


    public function __construct(App $app)
    {
        $this->app = $app;
    }


    public function setMiddlewareAliases($aliases = [])
    {
        $aliases = is_array($aliases) ? $aliases : [$aliases];

        $this->middlewareAliases = $aliases;

        return $this;
    }


    public function getMiddlewareAliases(): array
    {
        $this->middlewareAliases;
    }


    /**
     * @param String $namespace
     */
    public function setNamespace(String $namespace)
    {
        $this->namespace = trim($namespace, '\\');
    }


    public function setGlobalPatterns(array $patterns)
    {
        $this->patterns = $patterns;
    }


    public function domain(String $domain = null)
    {
        if ($domain !== null) {
            if (preg_match('/^https?:\/\//', $domain)) {
                $domain = str_replace(['https://', 'http://'], '', $domain);
            }

            $this->domain = $this->app->get('url')->scheme() . '://' . $domain;

            return $this;
        }

        $domain =  $this->domain !== null
            ? $this->domain
            : $this->app->get('url')->base();

        return rtrim($domain, '/');
    }



    /**
     * @param $methods
     * @param $path
     * @param $handler
     */
    public function add($methods, $path, $handler)
    {
        $this->methods = is_array($methods) ? $methods : [$methods];


        list($_path, $middleware, $pattern) = $this->parsePath($path);


        foreach ($this->methods as $method) {
            $this->routes[strtoupper($method)][] =  [
                'path' => $_path,
                'handler' => $handler,
                'ajax' => $this->ajax,
                'middleware' => $middleware,
                'pattern' => $pattern
            ];
        }

        $this->name = null;

        $this->pattern = [];

        $this->middleware = [];
    }


    /**
     * @param Closure $handler
     */
    public function ajax(Closure $handler)
    {
        $this->ajax = true;

        $handler();

        $this->ajax = false;
    }


    /**
     * @return bool|int|mixed|void
     * @throws RouteException
     * @throws NotFoundException
     * @throws Exception
     */
    public function run()
    {
        $requestUri = trim($this->app->get('url')->current(), '/');

        $method     = $this->getRequestMethod();

        $ajax       = $this->app->get('http')->isAjax();

        foreach ($this->routes[$method] as $resource) {
            if (!$ajax && $resource['ajax']) {
                continue;
            }

            $args    = [];

            $route   = rtrim($resource['path'], '/');

            $handler = $resource['handler'];

            if (preg_match('/({.+?})/', $route)) {
                list($args, $uri, $route) = $this->parseRoute($requestUri, $route, $resource['pattern'] ?? []);
            }

            if (!preg_match("#^$route$#", $requestUri)) {
                unset($this->routes[$method]);
                continue;
            }

            if (isset($uri)) {
                $this->parseRouteParams($uri, $args);
            }

            if (is_string($handler) && strpos($handler, '@')) {
                return $this->callAction($handler, $resource['middleware'], $args);
            }

            if (is_callable($handler)) {
                return $this->callHandler($handler, $resource['middleware'], $args);
            }

            throw new RouteException('Route Handler type undefined');
        }
        if (class_exists('NotFoundException')) {
            throw new NotFoundException;
        }

        abort(404);
    }


    /**
     * @param string $action
     * @param $middleware_array
     * @param $args
     * @return mixed
     * @throws NotFoundException|Exception
     */
    protected function callAction(string $handler, $middleware_array, $args)
    {

        list($controller, $method) = explode('@', $handler);

        if (strpos($controller, '/') !== false) {
            $controller = str_replace('/', '\\', $controller);
        }

        $class = "\\" . $this->namespace . "\\$controller";

        if (method_exists($class, $method)) {
            define('ACTION', strtolower($method));

            define('CONTROLLER', $controller);

            $this->callMiddleware($middleware_array);

            $args = Reflections::classMethodParameters(
                $class, $method, $args
            );

            $constructorArgs = [];
            if (method_exists($class, '__construct')) {
                $constructorArgs = Reflections::classMethodParameters(
                    $class, '__construct'
                );
            }

            $response = call_user_func_array([new $class(...$constructorArgs), $method], $args);

            if ($this->app->isInstance($response, 'response')) {
                return $response;
            }
            return $this->app->get('response')->appendContent($response);
        }

        if (class_exists('NotFoundException')) {
            throw new NotFoundException;
        }

        abort(404);
    }


    /**
     * @param callable $handler
     * @param $middleware_array
     * @param $args
     * @return mixed
     * @throws Exception
     */
    protected function callHandler(callable $handler, $middleware_array, $args)
    {
        $this->callMiddleware($middleware_array);

        $args = Reflections::functionParameters($handler, $args);

        $response = call_user_func_array($handler, $args);

        if ($this->app->isInstance($response, 'response')) {
            return $response;
        }
        return $this->app->get('response')->appendContent($response);
    }


    /**
     * @param array $middleware_array
     * @throws Exception
     */
    protected function callMiddleware(array $middleware_array)
    {
        if (!empty($middleware_array)) {
            foreach ($middleware_array as $middleware) {
                list($name, $excepts, $guard) = Middleware::getExceptsAndGuard($middleware);
                if (isset($this->middlewareAliases[$name])) {
                    Middleware::init($this->middlewareAliases[$name], $guard, $excepts);
                }
            }
        }
    }


    /**
     * @return string
     * @throws Exception
     */
    private function getRequestMethod(): string
    {
        $method = $this->app->get('request')->method('GET');

        return ($method === 'HEAD') ?  'GET' : $method;
    }


    /**
     * @param $middleware
     * @return $this
     */
    public function middleware($middleware): self
    {
        if (!empty($this->methods)) {
            foreach ($this->methods as $method) {
                $index = count($this->routes[$method]) - 1;

                $this->routes[$method][$index]['middleware'][] = $middleware;
            }
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }


    /**
     * @param array|string $name
     * @param null|string $value
     * @return Route
     */
    public function pattern($name, $value = null): self
    {
        $pattern = is_array($name) ? $name : [$name => $value];

        if (!empty($this->methods)) {
            foreach ($this->methods as $method) {
                $index = count($this->routes[$method]) - 1;

                $old = $this->routes[$method][$index]['pattern'];

                $this->routes[$method][$index]['pattern'] = array_merge($old, $pattern);
            }
        } else {
            $this->pattern = $pattern;
        }

        return $this;
    }


    /**
     * @param string $name
     * @return Route
     */
    public function name(String $name): self
    {
        if (!empty($this->methods)) {
            foreach ($this->methods as $method) {
                $index = count($this->routes[$method]) - 1;

                $path = $this->routes[$method][$index]['path'];

                $this->routes['NAMES'][$this->group_name . $name] = $path;
            }
        } else {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @param array $routes
     * @return $this
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return (array) $this->routes;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function importRouteFiles(array $files)
    {
        foreach ($files as $file) {
            require_once $file;
        }

        return $this;
    }


    /**
     * @param $name
     * @param array $parameters
     * @return mixed|string|string[]|null
     * @throws RouteException
     */
    public function getName($name, array $parameters = [])
    {
        if (isset($this->routes['NAMES'][$name])) {
            $route =  $this->routes['NAMES'][$name];

            if (strpos($route, '}') !== false) {
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $value) {
                        $route = str_replace(['{' . $key . '}', '{' . $key . '?}'], $value, $route);
                    }
                }

                $callback = static function ($match) {
                    if (strpos($match[0], '?') !== false) {
                        return '';
                    }

                    return $match[0];
                };

                $route = preg_replace_callback('/({.+?})/', $callback, $route);

                if (strpos($route, '}') !== false) {
                    throw new RouteException('Route url parameters required');
                }
            }

            return $route;
        }
        throw new RouteException("Route name [{$name}] not found");
    }


    public function flush()
    {
        $route  = new Route($this->app);

        $this->app->register('route', $route);

        return $route;
    }
}
