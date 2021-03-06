<?php

namespace TT\Session;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Session
 */


use Closure;
use Countable;
use Exception;
use ArrayAccess;
use TT\Engine\App;
use TT\Engine\Config;

class Session implements ArrayAccess, Countable
{
    protected $config;

    protected $drivers = [
        'database' => \TT\Session\Drivers\SessionDatabaseHandler::class,
        'redis' => \Predis\Session\Handler::class,
        'file' => \TT\Session\Drivers\SessionFileHandler::class,
    ];

    protected $app;



    public function __construct(App $app)
    {
        $this->app = $app;

        $this->config = $this->app['config']->get('session');
    }


    /**
     * @throws Exception
     */
    public function start()
    {
        if (!$this->isStarted()) {
            ini_set('session.cookie_httponly', $this->config['cookie']['http_only']);
            ini_set('session.use_only_cookies', $this->config['only_cookies']);
            ini_set('session.gc_maxlifetime', $this->config['lifetime']);
            ini_set('session.save_path', $this->config['files_location']);

            session_set_cookie_params(
                $this->config['lifetime'],
                $this->config['cookie']['path'],
                $this->config['cookie']['domain'],
                $this->config['cookie']['secure'],
                $this->config['cookie']['http_only']
            );

            session_name($this->config['cookie']['name']);

            if (
                isset($this->config['driver']) &&
                array_key_exists($this->config['driver'], $this->drivers)
            ) {
                $this->app->make(
                    $this->drivers[$this->config['driver']]
                )->register();
            }


            register_shutdown_function('session_write_close');


            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                session_start([
                    //'cache_limiter' => 'private',
                    //'read_and_close' => true,
                ]);
            } else {
                session_start();
            }
            $this->token();
        }
    }


    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return !(session_status() === PHP_SESSION_NONE);
    }


    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        if ($value instanceof Closure) {
            return $this->set($key, $value($this));
        }

        $_SESSION[$key] = $value;

        if ($this->config['regenerate'] === true) {
            $this->regenerate();
        }
    }


    /**
     * @return string
     */
    private function token(): String
    {
        if (!$this->has('_token')) {
            $token = base64_encode(
                openssl_random_pseudo_bytes(40)
            );

            $this->set('_token', $token);
        } else {
            $token = $this->get('_token');
        }

        return $token;
    }


    /**
     * @return mixed
     */
    public function prevUrl()
    {
        return $this->get('_prev_url');
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        if ($default instanceof Closure) {
            return $default($this);
        }

        return $default;
    }


    /**
     * @param $key
     * @param null $value
     * @return bool|mixed
     */
    public function flash($key, $value = null)
    {
        if ($value) {
            $_SESSION['_flash-data'][$key] = $value;
        } else {
            if (isset($_SESSION['_flash-data'][$key])) {
                $return = $_SESSION['_flash-data'][$key];

                unset($_SESSION['_flash-data'][$key]);

                if (empty($_SESSION['_flash-data'])) {
                    unset($_SESSION['_flash-data']);
                }
                return $return;
            }
            return false;
        }
    }


    /**
     * @param array $data
     * @return Session
     */
    public function setArray(array $data): Session
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }


    /**
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }


    /**
     * @param $key
     * @return Bool
     */
    public function has($key): Bool
    {
        return isset($_SESSION[$key]);
    }


    /**
     * @param $key
     * @return Session
     */
    public function delete($key): Session
    {
        if ($key instanceof Closure) {
            $this->delete($key($this));
        } elseif (is_array($key)) {
            foreach ($key as $value) {
                $this->delete($value);
            }
        } elseif (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }

        return $this;
    }


    /**
     * @return Session
     */
    public function regenerate(): self
    {
        session_regenerate_id(true);
        return $this;
    }


    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }


    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }


    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }


    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $value = $args[0] ?? null;

        return $value === null
            ? $this->get($method)
            : $this->set($method, $value);
    }


    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($_SESSION);
    }



    /**
     *
     */
    public function destroy()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
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
    public function offsetSet($offset, $value): void
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
    public function offsetUnset($offset): void
    {
        $this->delete($offset);
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
    public function offsetExists($offset): bool
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
        return count($this->all());
    }
}
