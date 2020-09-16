<?php

namespace TT\Translation;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Language
 */


use TT\Engine\App;
use ArrayAccess;

class Translator implements ArrayAccess
{
    protected $data = [];

    protected $locale;

    private $app;

    /**
     * Translator constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        if (!$this->locale) {
            if ($locale = $this->app['session']->get('_LOCALE')) {
                $this->locale = $locale;
            } else {
                $this->locale = $this->app->get('config')->get('app.locale', 'en');
            }
        }

        $this->prepare();
    }


    private function prepare(): void
    {
        foreach (glob($this->app->langPath($this->locale . '/*')) as $file) {
            $this->data[pathinfo($file, PATHINFO_FILENAME)] = require($file);
        }
    }


    /**
     * @param String $word
     * @param array $replace
     * @return array|String
     * @internal param Null $locale
     */
    public function translate(String $word, array $replace = [])
    {
        if (strpos($word, '.') !== false) {
            $data = $this->get($word);

            if ($data === null || empty($replace)) {
                return $data;
            }

            $keys = array_map(function ($key) {
                return ':' . $key;
            }, array_keys($replace));

            $values = array_values($replace);

            return str_replace($keys, $values, $data);
        }
        return $this->data[$word] ?? '';
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (strpos($key, '.')) {
            $item_recursive = explode('.', $key);

            $lang = $this->data;

            foreach ($item_recursive as $item) {
                $lang = $lang[$item] ?? false;
            }

            return $lang ?: $default;
        }
        return $this->data[$key] ?? $default;
    }


    /**
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }


    /**
     * @param string|null $locale
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     * @return Translator
     */
    public function setLocale(string $locale = null)
    {
        $this->app->get('session')->set('_LOCALE', $locale);

        $this->locale = $locale;

        return $this;
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
        return $this->get($offset);
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
        $this->data[$offset] = $value;
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
        if (isset($this->data[$offset])) {
            unset($this->data[$offset]);
        }
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
        return isset($this->data[$offset]);
    }
}
