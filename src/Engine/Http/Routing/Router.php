<?php

namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use Exception;
use TT\Engine\App;
use TT\Engine\Reflections;
use TT\Exceptions\RouteException;
use App\Exceptions\HttpNotFoundException;
use TT\Engine\Http\Pipeline\Pipeline;

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

    private $route;

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
     * @param Route $route
     * @return $this
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;

        return $this;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return ($this->route ?? new Route($this))->$name(...$arguments);
    }

    /**
     * @param array $methods
     * @param $uri
     * @param $callback
     * @return Route
     */
    public function add(array $methods, $uri, $callback)
    {
        $route = $this->route ?? new Route($this);

        $uri = $this->prefix . rtrim($uri, '/') . '/';
        $route = $route
            ->middleware($this->middleware)
            ->domain($this->getDomain())
            ->prependNamespace($this->namespace)
            ->pattern($this->patterns)
            ->prependName($this->name)
            ->prependPrefix($uri)
            ->setCallback($callback);

        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][$uri] = &$route;
        }

        $this->route = null;

        return $route;
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
     * @throws HttpNotFoundException
     * @throws Exception
     */
    public function run()
    {
        $requestUri = rtrim($this->app->get('url')->current(), '/');

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

            return $this->call($route, $arguments);
        }

        if (class_exists('HttpNotFoundException')) {
            throw new HttpNotFoundException;
        }

        abort(404);
    }


    private function call($route, $arguments)
    {
        if (is_array($route)) {
            $this->route = (new Route($this))->setAttributes($route);
        } else {
            $this->route = $route;
        }

        $callback = $this->route->getCallback();

        if (is_string($callback) && strpos($callback, '@')) {
            return $this->callController($arguments);
        }

        if (is_callable($callback)) {
            return $this->callHandler($arguments);
        }

        throw new RouteException('Route Callback type undefined');
    }



    /**
     * @param string $callback
     * @param $route
     * @param $args
     * @return mixed
     * @throws Exception
     */
    protected function callController($args)
    {

        [$controller, $method] = $this->route->getController(true);

        if (method_exists($controller, $method)) {

            $this->callMiddleware($this->route->getMiddleware());

            $args = Reflections::methodParameters(
                $controller,
                $method,
                $args
            );

            $constructorArgs = [];

            if (method_exists($controller, '__construct')) {
                $constructorArgs = Reflections::methodParameters(
                    $controller,
                    '__construct'
                );
            }

            $controller = new $controller(...$constructorArgs);

            if ($controller instanceof Controller) {
                $this->callMiddleware($controller->getMiddleware());

                return $this->app->get('response')
                    ->appendContent(
                        $controller->callAction($method, $args)
                    );
            } else {
                throw new RouteException(
                    get_class($controller) . ' must have an extension ' . Controller::class
                );
            }
        }

        if (class_exists('HttpNotFoundException')) {
            throw new HttpNotFoundException;
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
    protected function callHandler($arguments)
    {
        $this->callMiddleware($this->route->getMiddleware());

        $callback = $this->route->getCallback();

        $arguments = Reflections::functionParameters(
            $callback,
            $arguments
        );

        $response = $callback(...$arguments);

        return $this->app->get('response')->appendContent($response);
    }


    /**
     * @param array $middleware
     * @return void
     * @throws Exception
     */
    protected function callMiddleware(array $middleware)
    {
        $pipes = array_map(function ($value) {
            if (strpos($value, ':')) {
                [$value, $arguments] = explode(':', $value);
            }

            if (isset($this->middlewareAliases[$value])) {
                return $this->middlewareAliases[$value]
                    . (isset($arguments) ? ':' . $arguments : '');
            }
            throw new RouteException('Route middleware [' . $value . '] not found');
        }, $middleware);

        if (!empty($pipes)) {
            (new Pipeline($this->app))
                ->send($this->app['request'])
                ->pipe($pipes)
                ->then(function ($request) {
                    $this->app['request'] = $request;
                });
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
     * @return mixed
     * @throws RouteException
     */
    public function getName($name, array $parameters = [])
    {
        if (isset($this->routes['NAMES'][$name])) {
            return preg_replace_callback(
                '/({(.+?)}\/?)/',
                function ($match) use ($parameters, $name) {
                    if (isset($parameters[rtrim($match[2], '?')])) {
                        return $parameters[rtrim($match[2], '?')] . '/';
                    } elseif (rtrim($match[2], '?') !== $match[2]) {
                        return '';
                    } else {
                        throw new RouteException('Route [' . $name . '] parameter [' . $match[2] . '] required');
                    }
                },
                $this->routes['NAMES'][$name]
            );
        }
        throw new RouteException("Route name [{$name}] not found");
    }


    public function getCurrent()
    {
        return $this->route;
    }
}
