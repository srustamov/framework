<?php /** @noinspection ALL */

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
use JetBrains\PhpStorm\NoReturn;
use ReflectionException;
use RuntimeException;
use TT\Engine\Http\Pipeline\Pipeline;
use TT\Engine\Http\Request;
use TT\Engine\Http\Response;

class App implements ArrayAccess
{
    public const VERSION = '2.0';
    public const MAP = [
        'app'            => 'TT\Engine\App',
        'config'         => 'TT\Engine\Config',
        'array'          => 'TT\Arr',
        'authentication' => 'TT\Auth\Authentication',
        'cache'          => 'TT\Cache\Cache',
        'console'        => 'TT\Engine\Cli\Console',
        'cookie'         => 'TT\Cookie',
        'database'       => 'TT\Database\Builder',
        'email'          => 'TT\Mail\Email',
        'file'           => 'TT\File',
        'hash'           => 'TT\Hash',
        'html'           => 'TT\Html',
        'http'           => 'TT\Http',
        'input'          => 'TT\Input',
        'translator'     => 'TT\Translation\Translator',
        'middleware'     => 'TT\Engine\Http\Middleware',
        'openssl'        => 'TT\Encryption\OpenSsl',
        'jwt'            => 'TT\Auth\Jwt',
        'redirect'       => 'TT\Redirect',
        'redis'          => 'TT\Redis',
        'request'        => 'TT\Engine\Http\Request',
        'response'       => 'TT\Engine\Http\Response',
        'route'          => 'TT\Engine\Http\Routing\Router',
        'session'        => 'TT\Session\Session',
        'str'            => 'TT\Str',
        'string'         => 'TT\Str',
        'storage'        => 'TT\Storage',
        'url'            => 'TT\Uri',
        'validator'      => 'TT\Validator',
        'view'           => 'TT\View\View',
    ];
    public static array $classes = [];
    protected static ?self $instance = null;
    public array $paths = [
        'base'             => '',
        'public'           => 'public',
        'storage'          => 'storage',
        'lang'             => 'lang',
        'configs'          => 'configs',
        'envFile'          => '.env',
        'envCacheFile'     => 'storage/system/env',
        'configsCacheFile' => 'storage/system/configs.php',
        'routesCacheFile'  => 'storage/system/routes.php',
    ];
    protected array $routeMiddleware = [];
    protected array $middleware = [];
    private bool $boot = false;

    /**
     * App constructor.
     * Set application base path
     *
     * @param string|null $basePath
     */
    public function __construct(?string $basePath = null)
    {
        define('DS', DIRECTORY_SEPARATOR);

        $this->prepare($basePath);
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
            $this->paths['base'] = rtrim($basePath, DS);
        }

        chdir($this->paths['base']);

        static::$instance = self::$classes['app'] = $this;

        return $this;
    }

    /**
     * @param $object
     * @param $className
     * @return bool
     * @throws Exception
     */
    public static function isInstance($object, $className): bool
    {
        $instance = self::get($className);

        return ($object instanceof $instance);
    }

    /**
     * @param string $class
     * @param mixed ...$args
     * @return mixed
     * @throws RuntimeException
     * @throws ReflectionException
     */
    public static function get(string $class, ...$args)
    {
        if (isset(self::$classes[$class])) {
            return self::$classes[$class];
        }
        if ($instance = self::map($class)) {
            if (method_exists($instance, '__construct')) {
                $args = Reflections::methodParameters($instance, '__construct', $args);
            }
            return self::$classes[$class] = new $instance(...$args);
        }
        if (strpos($class, '\\')) {
            if ($instance = self::map($class, true)) {
                return self::get($instance, ...$args);
            }

            static::$classes[$class] = new $class(
                Reflections::methodParameters(
                    $class,
                    '__construct',
                    $args
                )
            );

            return self::$classes[$class];
        }
        throw new RuntimeException('Class not found [' . $class . ']');
    }

    /**
     * @param String|null $name
     * @param bool $isValue
     * @return array|bool|false|int|mixed|string
     */
    public static function map(string $name = null, bool $isValue = false)
    {
        if ($name === null) {
            return self::MAP;
        }

        if (!$isValue) {
            return self::MAP[strtolower($name)] ?? false;
        }

        return array_search($name, self::MAP, true);
    }

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    #[NoReturn]
    public static function end(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit(0);
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
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

            $this->callMiddleware($this->middleware);

            $this->setLocale();

            $this->boot = true;

            $this->afterBootstrap();
        }

        return $this;
    }

    public function isBoot(): bool
    {
        return $this->boot;
    }

    /**
     * Create application public path
     *
     * @param String|null $path
     */
    public function setPublicPath(string $path = null): void
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
            $this->paths['public'] = rtrim($this->paths['base'], DS)
                . DS
                . ltrim($this->paths['public'], DS);
        }
    }

    /**
     * @param $className
     * @param $object
     */
    public function singleton(string $className, $object): void
    {
        if ($object instanceof Closure) {
            self::$classes[$className] = $object($this);
        } elseif (is_string($object)) {
            self::$classes[$className] = new $object();
        } elseif (is_object($object)) {
            self::$classes[$className] = $object;
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function callImportantClasses(): void
    {
        (new LoadEnvironmentVariables($this))->handle();
        (new PrepareConfigurations($this))->handle();
        (new RegisterExceptionHandler($this))->handle();
    }

    /**
     *  Application aliases setting
     * @throws ReflectionException
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
     * @param $middleware
     * @throws ReflectionException
     */
    public function callMiddleware($middleware): void
    {
        (new Pipeline($this))
            ->send(self::get('request'))
            ->pipe($middleware)
            ->then(function ($request) {
                $this['request'] = $request;
            });
    }

    /**
     * Set application locale and timezone
     *
     * @return void
     * @throws ReflectionException
     */
    protected function setLocale(): void
    {
        $config = self::get('config');

        setlocale(LC_ALL, $config->get('datetime.setLocale'));

        date_default_timezone_set($config->get('datetime.time_zone', 'UTC'));
    }

    protected function afterBootstrap(): void
    {
        //
    }

    /**
     * Application Routing
     *
     * @return Response
     * @throws Exception
     */
    public function routing(): Response
    {
        /**@var $router Http\Routing\Router */
        $router = self::get('route');

        $router->setMiddlewareAliases($this->routeMiddleware);

        if (file_exists($file = $this->routesCacheFile())) {
            $router->setRoutes(require $file);
        } else {
            $router->importRouteFiles(
                glob($this->path('routes') . '/*.php')
            );
        }

        return CONSOLE ? self::get('response') : $router->run();
    }

    /**
     * @return string
     */
    public function routesCacheFile(): string
    {
        return $this->path($this->paths['routesCacheFile']);
    }

    public static function runningConsole()
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public static function isDebug()
    {
        return self::get('config')->get('app.debug');
    }

    /**
     * @param string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        return $this->paths['base'] . DS . ltrim($path, DS);
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
     * @noinspection PhpUnused
     */
    public function publicPath(string $path = ''): string
    {
        return $this->paths['public'] . DS . ltrim($path, DS);
    }

    /**
     * @param string $path
     * @return string
     * @noinspection PhpUnused
     */
    public function storagePath(string $path = ''): string
    {
        return $this->path($this->paths['storage'] . DS . ltrim($path, DS));
    }

    /**
     * @param string $path
     * @return string
     */
    public function configsPath(string $path = ''): string
    {
        return $this->path($this->paths['configs'] . DS . ltrim($path, DS));
    }

    /**
     * @param string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->paths['base'] . DS . 'app' . DS . ltrim($path, DS);
    }

    /**
     * @param string $path
     * @return string
     */
    public function langPath(string $path = ''): string
    {
        return $this->path($this->paths['lang'] . DS . ltrim($path, DS));
    }

    /**
     * @return string
     */
    public function configsCacheFile(): string
    {
        return $this->path($this->paths['configsCacheFile']);
    }

    /**
     * @return string
     */
    public function envCacheFile(): string
    {
        return $this->path($this->paths['envCacheFile']);
    }

    public function make($className, ...$args)
    {
        if (!class_exists($className)) {
            throw new RuntimeException(sprintf('Class [%s] not found', $className));
        }
        return new $className(
            ...Reflections::methodParameters(
            $className,
            '__construct',
            $args
        )
        );
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
    public function offsetGet(mixed $offset): mixed
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
    public function offsetSet(mixed $offset, mixed $value): void
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
    public function offsetUnset(mixed $offset): void
    {
        if (isset(self::$classes[$offset])) {
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
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, self::$classes);
    }
}
