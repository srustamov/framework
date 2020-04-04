<?php

namespace TT;

/**
 * @package  TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage  Library
 * @category   Redirect
 */



use TT\Engine\App;
use TT\Facades\Route;

class Redirect
{

    private $refresh;

    /**
     * Redirect constructor.
     */
    public function __construct()
    {
        if (func_num_args() > 0) {
            call_user_func_array([$this, 'to'], func_get_args());
        }
    }


    /**
     * @param $name
     * @param array $parameters
     * @return Redirect
     * @throws \RuntimeException
     */
    public function route($name, array $parameters = []): Redirect
    {
        return $this->to((string) Route::getName($name, $parameters));
    }


    /**
     * @param int $refresh
     * @param int $http_response_code
     * @return mixed
     * @throws \RuntimeException
     */
    public function back($refresh = 0, $http_response_code = 302)
    {
        if ($refresh) {
            $this->refresh = $refresh;
        }

        if ($back = App::get('http')->referer()) {
            $url = $back;
        } elseif (($back = App::get('session')->prevUrl())) {
            $url = $back;
        } else {
            $url = '';
        }

        return $this->to($url, $refresh, $http_response_code);
    }


    /**
     * @param string $url
     * @param int $refresh
     * @param int $http_response_code
     * @return Redirect
     * @throws \RuntimeException
     */
    public function to(string $url, $refresh = 0, $http_response_code = 302): self
    {
        $url = $this->prepareUrl($url);

        App::get('response')->redirect($url, $refresh, $http_response_code);

        return $this;
    }


    /**
     * @param $key
     * @param null $value
     * @return Redirect
     */
    public function with($key, $value = null): self
    {
        $data = is_array($key) ?: [$key => $value];

        foreach ($data as $name => $v) {
            App::get('session')->flash($name, $v);
        }

        return $this;
    }


    /**
     * @param $key
     * @param null $value
     * @return Redirect
     */
    public function withErrors($key, $value = null): self
    {
        $data = is_array($key) ? $key : [$key => $value];

        App::get('session')->flash('view-errors', $data);

        return $this;
    }


    /**
     * @param $url
     * @return mixed
     * @throws \RuntimeException
     */
    protected function prepareUrl($url)
    {
        if (empty(trim($url))) {
            throw new \RuntimeException('Redirect location empty url');
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            $url = App::get('url')->to($url);
        }

        return $url;
    }


    /**
     * @param $method
     * @param $args
     * @return $this
     */
    public function __call($method, $args)
    {
        if (func_num_args() > 0) {
            if (strlen($method) > 4 && strpos($method, 'with') === 0) {
                $method = strtolower($method);

                $var  = substr($method, 4);

                $args = is_array($args[0]) ? $args[0] : [$args[0] => $args[1] ?? null];

                App::get('session')->flash($var, $args[0]);
            } else {
                throw new \BadMethodCallException("Call to undefined method Redirect::{$method}()");
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function instance(): self
    {
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }
}
