<?php

namespace TT;

/**
 * @package  TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage  Library
 * @category   Uri
 */

use ReflectionException;
use TT\Engine\App;
use TT\Engine\Http\Routing\Router;

class Uri
{

    private $host;

    private $path;

    private $scheme;

    private $base;

    private $router;


    public function __construct(string $base_url = null, Router $router = null)
    {
        if (!$base_url && class_exists(App::class)) {
            $base_url = App::get('config')->get('app.url', null);
        }
        $this->base = $base_url;

        $this->router = $router;
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return string
     */
    public function to($url = '', array $parameters = []): string
    {
        return $this->getScheme() . '://' . $this->getHost() . '/' .
            (!empty($parameters)
                ? trim($url, '/') . '/?' . http_build_query($parameters)
                : ltrim($url, '/'));
    }


    /**
     * @param $name
     * @param array $parameters
     * @return null|string
     */
    public function getRouteUri($name, array $parameters = [])
    {
        if ($this->router) {
            return $this->router->getName($name, $parameters);
        }

        return null;
    }


    /**
     * @param $host
     * @return $this
     */
    public function withHost($host)
    {
        $this->host = $host;

        return $this;
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        if (!$this->path) {
            $path = urldecode(
                parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
            );
            $path = str_replace(' ', '', $path);

            $this->path = ($path === '' || $path === '/') ? '/' : rtrim($path, '/');
        }

        return $this->path;
    }


    /**
     * @param string $path
     */
    public function withPath(string $path)
    {
        $this->path = $path;
    }


    public function withScheme(string $scheme)
    {
        $this->scheme = $scheme;
    }


    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme ?? ($this->isSecure() ? 'https' : 'http');
    }


    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    }


    /**
     * @param String $url
     * @param array $parameters
     * @return String
     */
    public function getBase($url = '', array $parameters = []): String
    {
        if (preg_match('/^(https?:\/\/)/', $url)) {
            return trim($url, '/') . (!empty($parameters) ? '/?' . http_build_query($parameters) : '/');
        }
        
        if (!$this->base || empty($this->base)) {
            $this->base = $this->getScheme() . '://' . $this->getHost();
        }

        return rtrim($this->base, '/')
            . '/' . ltrim($url, '/')
            . (!empty($parameters) ? '/?' . http_build_query($parameters) : '');
    }


    /**
     * @param null $url
     * @return String
     */
    public function getCurrent($url = null): String
    {
        return $this->getScheme() . '://'
            . $this->getHost() . '/'
            . trim($this->getPath(), '/') . '/'
            . $url;
    }


    /**
     * @return string
     */
    public function getHost(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            $elements = explode(',', $host);
            $host = trim(end($elements));
        } else {
            $host = $_SERVER['HTTP_HOST'] ??
                $_SERVER['SERVER_NAME'] ??
                $_SERVER['SERVER_ADDR'] ??
                '';
        }

        return trim($host);
    }


    /**
     * @param Int $number
     * @return Bool|Mixed
     */
    public function getSegment(Int $number)
    {
        return $this->getSegments()[$number] ?? false;
    }


    /**
     * @return array
     */
    public function getSegments(): array
    {
        return array_filter(explode('/', $this->getPath()));
    }
}
