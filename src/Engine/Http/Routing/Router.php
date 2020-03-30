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

class Router
{
    use Group, Methods, Parser;

    public $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => [],
        'PATCH' => [],
        'NAMES' => [],
    ];

    private $middlewareAliases = [];

    private $middleware = [];

    private $namespace = 'App\Controllers';

    private $patterns = [];

    private $domain;

    private $prefix;

    private $builder;

    private $name;

    /**@var App */
    private $app;


    /**
     * Router constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param Route $builder
     * @return $this
     */
    public function setBuilder(Route $builder): self
    {
        $this->builder = $builder;

        return $this;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return ($this->builder ?? new Route($this))->$name(...$arguments);
    }

    /**
     * @param $methods
     * @param $uri
     * @param $callback
     * @return Route
     */
    public function add($methods, $uri, $callback)
    {
        $builder = $this->builder ?? new Route($this);

        $uri = $this->prefix . rtrim($uri, '/') . '/';
        $builder = $builder
            ->middleware($this->middleware)
            ->domain($this->getDomain())
            ->prependNamespace($this->namespace)
            ->pattern($this->patterns)
            ->prependName($this->name)
            ->prependPrefix($uri)
            ->setCallback($callback);

        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][$uri] = &$builder;
        }

        $this->builder = null;

        return $builder;
    }


    /**
     * @param array $aliases
     * @return $this
     */
    public function setMiddlewareAliases($aliases = []): self
    {
        $aliases = is_array($aliases) ? $aliases : [$aliases];

        $this->middlewareAliases = $aliases;

        return $this;
    }


    /**
     * @return array
     */
    public function getMiddlewareAliases(): array
    {
        return $this->middlewareAliases;
    }

    /**
     * @param String|null $domain
     * @return $this|string
     */
    public function getDomain(String $domain = null)
    {
        if ($domain) {
            if (preg_match('/^https?:\/\//', $domain)) {
                $domain = str_replace(['https://', 'http://'], '', $domain);
            }

            $this->domain = $this->app->get('url')->scheme() . '://' . $domain;

            return $this;
        }

        $domain = $this->domain ?? $this->app->get('url')->base();

        return rtrim($domain, '/');
    }


    /**
     * @return mixed
     * @throws RouteException
     * @throws NotFoundException
     * @throws Exception
     */
    public function run()
    {
        $requestUri = trim($this->app->get('url')->current(), '/');

        foreach ($this->routes[$this->getRequestMethod()] as $path => $route) {

            $arguments = [];

            $path = rtrim($route['domain'] . $path, '/');
            
            if (preg_match('/({.+?})/', $path)) {
                [$arguments, $uri, $path] = $this->parseRoute(
                    $requestUri,
                    $path,
                    $route['patterns']
                );
            }

            if (!preg_match("#^$path$#", $requestUri)) {
                continue;
            }

            if (isset($uri)) {
                $this->parseRouteParams($uri, $arguments);
            }

            return $this->call($route,$arguments);
        }

        if (class_exists('NotFoundException')) {
            throw new NotFoundException;
        }

        abort(404);
    }


    private function call($route,$arguments)
    {
        $callback = $route['callback'];

        if (is_string($callback) && strpos($callback, '@')) {
            return $this->callAction($callback, $route, $arguments);
        }

        if (is_callable($callback)) {
            return $this->callHandler($callback, $route, $arguments);
        }

        throw new RouteException('Route Handler type undefined');
    }



    /**
     * @param string $callback
     * @param $route
     * @param $args
     * @return mixed
     * @throws Exception
     */
    protected function callAction(string $callback, $route, $args)
    {

        [$controller, $method] = explode('@', $callback,2);

        if (strpos($controller, '/') !== false) {
            $controller = str_replace('/', '\\', $controller);
        }

        $class = "\\" . trim($route['namespace'], '\\') . "\\$controller";

        if (method_exists($class, $method)) {

            define('ACTION', strtolower($method));

            define('CONTROLLER', $controller);

            $this->callMiddleware($route['middleware']);

            $args = Reflections::methodParameters(
                $class,
                $method,
                $args
            );

            $constructorArgs = [];

            if (method_exists($class, '__construct')) {
                $constructorArgs = Reflections::methodParameters(
                    $class,
                    '__construct'
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
     * @param callable $callback
     * @param $route
     * @param $args
     * @return mixed
     * @throws Exception
     */
    protected function callHandler(callable $callback, $route, $arguments)
    {
        $this->callMiddleware($route['middleware']);

        $arguments = Reflections::functionParameters($callback, $arguments);

        $response = $callback(...$arguments);

        if ($this->app->isInstance($response, 'response')) {
            return $response;
        }
        return $this->app->get('response')->appendContent($response);
    }


    /**
     * @param array $middleware
     * @return void
     * @throws Exception
     */
    protected function callMiddleware(array $middleware)
    {
        foreach ($middleware as $object) {
            [$name, $excepts, $guard] = Middleware::getExceptsAndGuard($object);
            if (isset($this->middlewareAliases[$name])) {
                Middleware::init($this->middlewareAliases[$name], $guard, $excepts);
            }
        }
    }


    /**
     * @return string
     * @throws Exception
     */
    private function getRequestMethod(): string
    {
        $method = $this->app->get('request')->getMethod('GET');

        return ($method === 'HEAD') ? 'GET' : $method;
    }


    /**
     * @param array $routes
     * @return $this
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->routes as $method => $value) {
            if ($method === 'NAMES') {
                continue;
            }
            foreach ($value as $path => $router) {
                $routes[$method][$path] = $router->getAttributes();
            }
        }

        $routes['NAMES'] = array_filter($this->routes['NAMES']);

        return $routes;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function importRouteFiles(array $files): self
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
            $route = $this->routes['NAMES'][$name];

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
}
