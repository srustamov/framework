<?php

namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */


use TT\Database\Orm\Model;
use function file_get_contents;
use function in_array;
use Countable;
use ArrayAccess;
use Serializable;
use JsonSerializable;
use TT\Engine\App;

/**
 * @method all()
 * @method only(...$names)
 * @method except()
 * @method add()
 * @method has()
 * @method map($callback)
 * @method filter($callback)
 */
class Request implements ArrayAccess, Countable, Serializable, JsonSerializable
{
    /**@var Parameters */
    public $request = [];

    /**@var Parameters */
    public $query = [];

    /**@var Parameters */
    public $input = [];

    /**@var Parameters */
    public $files = [];

    /**@var Parameters */
    public $cookies = [];

    /**@var Parameters */
    public $server = [];

    /**@var Parameters */
    public $headers = [];

    public $routeParams = [];

    public $method;

    /**@var App */
    private $application;


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

        $this->method = $this->getMethod('GET');

        if (
            0 === strpos($this->headers->get('Content-Type'), 'application/x-www-form-urlencoded')
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
    protected function prepareInputData()
    {
        if ($this->isJson()) {
            $content = file_get_contents('php://input');

            $data = json_decode($content,true);

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
     * @param $key
     * @return bool|mixed
     */
    public function params($key)
    {
        return $this->routeParams[$key] ?? false;
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
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
     * @return Parameters|null
     */
    public function input($name = null, $default = false): ?Parameters
    {
        if ($name) {
            return $this->input->get($name, $default);
        }
        return $this->input;
    }


    /**
     * @param null $key
     * @return mixed|App
     */
    public function session($key = null)
    {
        if ($key === null) {
            return $this->app('session');
        }

        return $this->app('session')->get($key);
    }


    /**
     * @param null $key
     * @return Parameters|null
     */
    public function cookie($key = null): ?Parameters
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies->get($key);
    }


    /**
     * @return null|Model
     */
    public function user(): ?Model
    {
        return $this->app('authentication')->user();
    }


    /**
     * @param string|null $key
     * @param null $default
     * @return Parameters|null
     */
    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server->get(strtoupper($key), $default);
    }


    /**
     * @param string|null $name
     * @param null $default
     * @return Parameters|null
     */
    public function header(string $name = null, $default = null): ?Parameters
    {
        if ($name === null) {
            return $this->headers;
        }
        return $this->headers->get($name, $default);
    }


    /**
     * @param string $name
     * @return UploadedFile
     */
    public function file(string $name):?UploadedFile
    {
        return $this->files->get($name);
    }


    /**
     * @param string $default
     * @return string
     */
    public function getMethod(string $default = 'GET'): string
    {
        if ($this->method === null) {
            $method = $this->server('request_method');

            if ($method === 'POST') {
                $xhmo = $this->headers->get('X-HTTP-Method-Override');

                if ($xhmo && in_array($xhmo, array('PUT', 'DELETE', 'PATCH'))) {
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
        return $this->getMethod() === $method;
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->headers->has('Accept') &&
            strpos($this->headers->get('Accept'), 'application/json') === 0;
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
     * @return mixed|null
     */
    public function bearerToken($default = null)
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
     */
    public function ip()
    {
        return $this->app('http')->ip();
    }

    /**
     * @return mixed
     */
    public function url()
    {
        return $this->app('url')->request();
    }


    /**
     * @param null $method
     * @return bool|mixed|string
     */
    public function controller($method = null)
    {
        if (!$method) {
            return defined('CONTROLLER') ? CONTROLLER : false;
        }

        return defined('ACTION') ? ACTION : false;
    }


    /**
     * @param array $roles
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
     * @return mixed|App
     */
    public function app($class = null)
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
    public function offsetGet($offset)
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
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
    {
        $this->request->remove($offset);
    }

    /**
     * Whether a offset exists
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
    public function offsetExists($offset)
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
    public function count()
    {
        return count($this->request);
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize($this->request->all());
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unSerialize($serialized)
    {
        unserialize($serialized,['allowed_classes' => []]);
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
    public function jsonSerialize(): array
    {
        return $this->request->all();
    }
}
