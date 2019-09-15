<?php

namespace TT\Engine;

/**
 * Application class
 *
 * @author Samir Rustamov <rustemovv96@gmail.com>
 * @link   https://github.com/srustamov/tt
 */

use ArrayAccess;
use Closure;
use Exception;
use RuntimeException;
use TT\Engine\Http\Middleware;
use TT\Engine\Http\Request;
use TT\Engine\Http\Response;

class App implements ArrayAccess
{
    public const VERSION = '1.2';

    public static $classes = [];

    protected $boot = false;

    protected $middleware = [];

    protected $routeMiddleware = [];

    public $paths = [
        'base' => '',
        'public' => 'public',
        'storage' => 'storage',
        'lang' => 'lang',
        'configs' => 'app/Config',
        'envFile' => '.config',
        'envCacheFile' => 'storage/system/config',
        'configsCacheFile' => 'storage/system/configs.php',
        'routesCacheFile' => 'storage/system/routes.php',
    ];


    protected static $instance;


    /**
     * App constructor.
     * Set application base path
     *
     * @param string $basePath
     */
    public function __construct(string $basePath = null)
    {
        $this->prepare($basePath);
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * @param string|null $basePath
     * @return $this
     */
    protected function prepare(string $basePath = null): self
    {
        if (!defined('CONSOLE')) {
            define('CONSOLE', PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }

        if ($basePath === null) {
            $this->paths['base'] = dirname(__DIR__, 4);
        } else {
            $this->paths['base'] = rtrim($basePath, DIRECTORY_SEPARATOR);
        }

        chdir($this->paths['base']);

        static::$instance = self::$classes['app'] = $this;

        return $this;
    }

    /**
     * Application bootstrapping
     *
     * @return $this
     * @throws Exception
     */
    public function bootstrap(): self
    {
        if (!$this->isBoot()) {
            $this->setPublicPath();

            $this->singleton('request', new Request($this));

            $this->callImportantClasses();

            $this->setAliases();

            $this->middleware($this->middleware);

            $this->setLocale();

            $this->boot = true;

            $this->afterBootstrap();
        }

        return $this;
    }


    protected function afterBootstrap()
    {
        //
    }


    public function isBoot(): bool
    {
        return $this->boot;
    }

    protected function callImportantClasses(): void
    {
        (new LoadEnvVariables($this))->handle();
        (new PrepareConfigs($this))->handle();
        (new RegisterExceptionHandler($this))->handle();
    }


    /**
     * @param array|string $name
     * @throws Exception
     */
    protected function middleware($name): void
    {
        $names = is_array($name) ? $name : [$name];
        foreach ($names as $middleware) {
            Middleware::init($middleware);
        }
    }

    /**
     *  Application aliases setting
     */
    protected function setAliases(): void
    {
        $aliases = self::get('config')->get('aliases', []);

        $aliases['App'] = get_class($this);

        foreach ($aliases as $key => $value) {
            class_alias('\\' . $value, $key);
        }
    }

    /**
     * Set application locale and timezone
     *
     * @return void
     */
    protected function setLocale(): void
    {
        setlocale(LC_ALL, self::get('config')->get('datetime.setLocale'));

        date_default_timezone_set(self::get('config')->get('datetime.time_zone', 'UTC'));
    }


    /**
     * Application Routing
     *
     * @return Response
     * @throws Exception
     */
    public function routing(): Response
    {
        /**@var $route Http\Routing\Route */
        $route = self::get('route');

        $route->setMiddlewareAliases($this->routeMiddleware);

        if (file_exists($file = $this->routesCacheFile())) {
            $route->setRoutes(require $file);
        } else {
            $route->importRouteFiles(
                glob($this->path('routes') . '/*')
            );
        }
        return CONSOLE ? self::get('response') : $route->run();
    }

    /**
     * Create application public path
     *
     * @param String|null $path
     */
    public function setPublicPath(String $path = null): void
    {
        if ($path !== null) {
            $this->paths['public'] = $path;
        } elseif (
            isset($_SERVER['SCRIPT_FILENAME']) &&
            !empty($_SERVER['SCRIPT_FILENAME']) &&
            !CONSOLE
        ) {
            $parts = explode('/', $_SERVER['SCRIPT_FILENAME']);
            array_pop($parts);

            $this->paths['public'] = implode('/', $parts);
        } else {
            $this->paths['public'] = rtrim($this->paths['base'], DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . ltrim($this->paths['public'], DIRECTORY_SEPARATOR);
        }
    }

    /**
     * @return string
     */
    public function envFile(): string
    {
        return $this->path($this->paths['envFile']);
    }

    /**
     * @param string $path
     * @return string
     */
    public function publicPath($path = ''): string
    {
        return $this->paths['public'] . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $path
     * @return string
     */
    public function path($path = ''): string
    {
        return $this->paths['base'] . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $path
     * @return string
     */
    public function storagePath($path = ''): string
    {
        return $this->path($this->paths['storage'] . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }

    public function configsPath($path = ''): string
    {
        return $this->path($this->paths['configs'] . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }

    public function appPath($path = ''): string
    {
        return $this->paths['base']
            . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
            . ltrim($path, DIRECTORY_SEPARATOR);
    }

    public function langPath($path = ''): string
    {
        return $this->path($this->paths['lang'] . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }

    public function configsCacheFile(): string
    {
        return $this->path($this->paths['configsCacheFile']);
    }

    public function routesCacheFile(): string
    {
        return $this->path($this->paths['routesCacheFile']);
    }

    public function envCacheFile(): string
    {
        return $this->path($this->paths['envCacheFile']);
    }

    public function classes(String $name = null, Bool $isValue = false)
    {

        $classes = array(
            'app' => "TT\Engine\App",
            'array' => 'TT\Libraries\Arr',
            'authentication' => 'TT\Libraries\Auth\Authentication',
            'cache' => 'TT\Libraries\Cache\Cache',
            'console' => 'TT\Engine\Cli\Console',
            'cookie' => 'TT\Libraries\Cookie',
            'database' => 'TT\Libraries\Database\Database',
            'email' => 'TT\Libraries\Mail\Email',
            'file' => 'TT\Libraries\File',
            'hash' => 'TT\Libraries\Hash',
            'html' => 'TT\Libraries\Html',
            'http' => 'TT\Libraries\Http',
            'input' => 'TT\Libraries\Input',
            'lang' => 'TT\Libraries\Language',
            'language' => 'TT\Libraries\Language',
            'middleware' => 'TT\Engine\Http\Middleware',
            'openssl' => 'TT\Libraries\Encryption\OpenSsl',
            'jwt' => 'TT\Libraries\Auth\Jwt',
            'redirect' => 'TT\Libraries\Redirect',
            'redis' => 'TT\Libraries\Redis',
            'request' => 'TT\Engine\Http\Request',
            'response' => 'TT\Engine\Http\Response',
            'route' => 'TT\Engine\Http\Routing\Route',
            'session' => 'TT\Libraries\Session\Session',
            'str' => 'TT\Libraries\Str',
            'string' => 'TT\Libraries\Str',
            'storage' => 'TT\Libraries\Storage',
            'url' => 'TT\Libraries\Url',
            'validator' => 'TT\Libraries\Validator',
            'view' => 'TT\Libraries\View\View',
        );

        if ($name === null) {
            return $classes;
        }

        if (!$isValue) {
            return $classes[strtolower($name)] ?? false;
        }

        return array_search($name, $classes, true);
    }

    /**
     * @param string $class
     * @param mixed ...$args
     * @return mixed
     * @throws RuntimeException
     */
    public static function get(string $class, ...$args)
    {
        if (isset(static::$classes[$class])) {
            return static::$classes[$class];
        }
        if ($instance = self::getInstance()->classes($class)) {
            if (method_exists($instance, '__construct')) {
                $args = Reflections::methodParameters($instance, '__construct', $args);
            }
            static::$classes[$class] = new $instance(...$args);
            return static::$classes[$class];
        }
        if (strpos($class, '\\')) {
            if ($instance = self::getInstance()->classes($class, true)) {
                return static::get($instance, ...$args);
            }

            static::$classes[$class] = new $class(Reflections::methodParameters($class, '__construct', $args));

            return static::$classes[$class];
        }
        throw new RuntimeException('Class not found [' . $class . ']');
    }

    /**
     * @param $className
     * @param $object
     */
    public function singleton($className, $object): void
    {
        if ($object instanceof Closure) {
            static::$classes[$className] = $object($this);
        } elseif (is_string($object)) {
            static::$classes[$className] = new $object();
        } elseif (is_object($object)) {
            static::$classes[$className] = $object;
        }
    }


    /**
     * @param $object
     * @param $className
     * @return bool
     * @throws Exception
     */
    public static function isInstance($object, $className): bool
    {
        $instance = static::get($className);

        return ($object instanceof $instance);
    }

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        return self::$instance;
    }


    public static function end(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit();
    }


    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        return self::get($offset);
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
        $this->singleton($offset, $value);
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
        if(isset(self::$classes[$offset])) {
            unset(self::$classes[$offset]);
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
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, self::$classes);
    }
}
