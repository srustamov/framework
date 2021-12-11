<?php /** @noinspection DuplicatedCode */

namespace TT\Engine;

use ArrayAccess;
use Countable;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */



class Config implements ArrayAccess, Countable
{
    private array $configurations;


    /**
     * Config constructor.
     * @param array $configurations
     */
    public function __construct(array $configurations = [])
    {
        $this->configurations = $configurations;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        if (str_contains($key, '.')) {
            $items_recursive = explode('.', $key);

            $config = $this->configurations;

            foreach ($items_recursive as $item) {
                if (array_key_exists($item, $config)) {
                    $config = $config[$item];
                } else {
                    return false;
                }
            }
            return true;
        }
        return array_key_exists($key, $this->configurations);
    }


    /**
     * @return array
     */
    public function all(): array
    {
        return $this->configurations;
    }


    /**
     * @param $extension
     * @param null $default
     * @return mixed
     */
    public function get($extension, $default = null): mixed
    {
        if (str_contains($extension, '.')) {
            $item_recursive = explode('.', $extension);

            $config = $this->configurations;

            foreach ($item_recursive as $item) {
                $config = $config[$item] ?? false;
            }

            return $config ?: $default;
        }

        return $this->configurations[$extension] ?? $default;
    }


    /**
     * @param $key
     * @param null $value
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            static::setRecursive($this->configurations, $k, $v);
        }
    }


    /**
     * @param String $key
     */
    public function forget(String $key)
    {
        if (str_contains($key, '.')) {
            static::forgetRecursive($this->configurations, $key);
        } elseif ($this->has($key)) {
            unset($this->configurations[$key]);
        }
    }


    /**
     * @param $key
     */
    public function delete($key)
    {
        $this->forget($key);
    }


    /**
     * @param $key
     * @param $value
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }


    /**
     * @param $key
     * @param $value
     */
    public function push($key, $value)
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }


    /**
     * @param $configurations
     * @param $key
     * @param $value
     * @return void
     */
    private static function setRecursive(&$configurations, $key, $value): void
    {
        if ($key === null) {
            $configurations = $value;
            return;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($configurations[$key]) || ! is_array($configurations[$key])) {
                $configurations[$key] = [];
            }
            $configurations = &$configurations[$key];
        }
        $configurations[array_shift($keys)] = $value;
    }


    /**
     * @param $configurations
     * @param $key
     * @return void
     */
    private static function forgetRecursive(&$configurations, $key): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($configurations[$key]) || ! is_array($configurations[$key])) {
                return;
            }

            $configurations = &$configurations[$key];
        }

        unset($configurations[array_shift($keys)]);
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
    public function offsetSet(mixed $offset, mixed $value)
    {
        $this->set($offset, $value);
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
        $this->forget($offset);
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
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
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
        return count($this->configurations);
    }
}
