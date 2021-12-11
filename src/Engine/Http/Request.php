<?php

namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use JetBrains\PhpStorm\Pure;
use ReflectionException;
use TT\Database\Orm\Model;
use Countable;
use ArrayAccess;
use Serializable;
use JsonSerializable;
use TT\Engine\App;

/**
 * @method only(...$names)
 * @method except()
 * @method add()
 * @method has()
 * @method map($callback)
 * @method filter($callback)
 */
class Request implements ArrayAccess, Countable, Serializable, JsonSerializable
{
    public array|Parameters $request = [];

    public array|Parameters $query = [];

    public array|Parameters $input = [];

    public array|UploadedFile $files = [];

    public array|Parameters $cookies = [];

    public array|Parameters $server = [];

    public array|Parameters $headers = [];

    public array $routeParams = [];

    public ?string $method = null;

    /**@var App */
    private App $application;


    /**
     * Request constructor.
     * @param App $application
     */
    public function __construct(App $application)
    {
        $this->application = $application;

        $this->prepare();
    }


    /**
     * Prepare request data
     * @return $this
     */
    public function prepare(): self
    {
        $this->server = new Parameters($_SERVER);

        $this->headers = new Parameters(getallheaders());

        $this->cookies = new Parameters($_COOKIE);

        $this->query = new Parameters($_GET);

        $this->input = new Parameters($this->prepareInputData());

        $this->files = new UploadedFile($_FILES);

        $this->method = $this->getMethod();

        if (
            str_starts_with($this->headers->get('Content-Type'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($this->method), ['PUT', 'DELETE', 'PATCH'])
        ) {
            $this->request = $this->input;
        } else {
            $this->request = new Parameters($_POST);
        }

        return $this;
    }


    /**
     * @return mixed
     */
    protected function prepareInputData(): mixed
    {
        if ($this->isJson()) {
            $content = file_get_contents('php://input');

            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
            unset($data);
        }
        parse_str(file_get_contents('php://input'), $data);


        return $data;
    }


    /**
     * @param $key
     * @param null $value
     * @noinspection PhpUnused
     */
    public function setRouteParams($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $_value) {
                $this->routeParams[$name] = $_value;
            }
        } else {
            $this->routeParams[$key] = $value;
        }
    }

    /**
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public function routeParams($key = null, $default = null): mixed
    {
        if (!$key) {
            return $this->routeParams;
        }
        return $this->routeParams[$key] ?? $default;
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        if ($this !== $result = $this->query->get($key, $this)) {
            return $result;
        }

        if ($this !== $result = $this->request->get($key, $this)) {
            return $result;
        }

        return $default;
    }


    /**
     * @param null $name
     * @param bool $default
     * @return mixed
     */
    public function input($name = null, mixed $default = false): mixed
    {
        if ($name) {
            return $this->input->get($name, $default);
        }
        return $this->input;
    }

    #[Pure]
    public function all(): array
    {
        return array_merge(
            $this->query->all(),
            $this->request->all(),
            $this->input->all(),
        );
    }


    /**
     * @param null $key
     * @return mixed
     * @throws ReflectionException
     */
    public function session($key = null): mixed
    {
        if ($key === null) {
            return $this->app('session');
        }

        return $this->app('session')->get($key);
    }


    /**
     * @param null $key
     * @return mixed
     */
    public function cookie($key = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies->get($key);
    }


    /**
     * @return null|Model
     * @throws ReflectionException
     */
    public function user(): ?Model
    {
        return $this->app('authentication')->user();
    }


    /**
     * @param string|null $key
     * @param null $default
     * @return mixed
     */
    public function server(string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server->get(strtoupper($key), $default);
    }


    /**
     * @param string|null $name
     * @param null $default
     * @return Parameters|array|null
     */
    public function header(string $name = null, $default = null): Parameters|array|null
    {
        if ($name === null) {
            return $this->headers;
        }
        return $this->headers->get($name, $default);
    }


    /**
     * @param string $name
     * @return UploadedFile|null
     */
    public function file(string $name): ?UploadedFile
    {
        return $this->files->get($name);
    }


    /**
     * @param string $default
     * @return string
     */
    public function getMethod(string $default = 'GET'): string
    {
        if (is_null($this->method)) {
            $method = $this->server('request_method');

            if ($method === 'POST') {

                $xhmo = $this->headers->get('X-HTTP-Method-Override');

                if (in_array($xhmo, array('PUT', 'DELETE', 'PATCH'))) {
                    $method = $xhmo;
                }
            }
        } else {
            $method = $this->method;
        }

        return $method ?: $default;
    }


    /**
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->headers->has('Accept') &&
            str_starts_with($this->headers->get('Accept'), 'application/json');
    }


    /**
     * @return bool
     */
    public function ajax(): Bool
    {
        return ($this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
    }


    /**
     * @param null $default
     * @return mixed
     */
    public function bearerToken($default = null): mixed
    {
        if ($this->headers->has('Authorization')) {
            $authorization = $this->headers->get('Authorization');
            if (preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
                $token = $matches[1];
            }
        }

        return $token ?? $default;
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    public function ip(): mixed
    {
        return $this->app('http')->ip();
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    public function url(): mixed
    {
        return $this->app('url')->getCurrent();
    }


    /**
     * @param null $action
     * @return null|string
     * @throws ReflectionException
     */
    public function controller(mixed $action = null):?string
    {
        if ($route = $this->app('route')->getCurrent()) {
            if ($callback = $route->getController($action)) {
                return $action ? ($callback[1] ?? null) : ($callback[0]);
            }
        }

        return null;
    }


    /**
     * @param array $roles
     * @throws ReflectionException
     */
    public function validate(array $roles)
    {
        $validation = $this->app('validator')->make($this->all(), $roles);

        if (!$validation->check()) {
            $this->app('redirect')->back()->withErrors($validation->messages());

            $this->app('response')->send();
        }
    }

    /**
     * @param null $class
     * @return mixed
     * @throws ReflectionException
     */
    public function app($class = null): mixed
    {
        if ($class) {
            return $this->application::get($class);
        }
        return $this->application;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->request->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return true;
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value)
    {
        return $this->request->set($name, $value);
    }


    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->request->{$method}(...$args);
    }


    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->request[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet(mixed $offset, mixed $value)
    {
        $this->request[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset(mixed $offset)
    {
        $this->request->remove($offset);
    }

    /**
     * Whether  offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->request->has($offset);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return count($this->request);
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize(): string
    {
        return serialize($this->request->all());
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $data <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unSerialize(string $data)
    {
        unserialize($data, ['allowed_classes' => []]);
    }


    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return array The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    #[Pure]
    public function jsonSerialize(): array
    {
        return $this->request->all();
    }
}
