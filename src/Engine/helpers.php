<?php
/**
 * Application helper Functions
 *
 * @category Helper_Functions
 * @author   Samir Rustamov <rustemovv96@gmail.com>
 * @link     https://github.com/srustamov/TT
 */


use TT\Engine\App;
use TT\Engine\Http\Response;

function app(string $class = null)
{
    if ($class === null) {
        return App::getInstance();
    }
    return App::get($class);
}


function auth(string $guard = null)
{
    if ($guard !== null) {
        return App::get('authentication')->guard($guard);
    }
    return App::get('authentication');
}


if (!function_exists('getAllHeaders')) {
    function getAllHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}


/**
 * @param String|null $name
 * @param null $default
 * @return mixed
 * @throws Exception
 */
function config(string $name = null, $default = null)
{
    if ($name === null) {
        return App::get('config');
    }
    return App::get('config')->get($name, $default);
}


function setting($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}


/**
 * @param String $file
 * @param bool $once
 * @return mixed
 * @throws Exception
 */
function import(string $file, $once = true)
{
    return App::get('file')->import($file, $once);
}


/**
 * @param $directory
 * @param bool $once
 * @throws Exception
 */
function importFiles($directory, $once = true)
{
    foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR) . '/*') as $file) {
        import($file, $once);
    }
}


/**
 * @param $value
 * @param null $hash
 * @return mixed
 */
function bcrypt($value, $hash = null)
{
    if ($hash === null) {
        return App::get('hash')->make($value);
    }

    return App::get('hash')->check($value, $hash);
}

/**
 * @return mixed
 */
function openssl()
{
    return App::get('openssl');
}


/**
 * @param string $path
 * @return mixed
 */
function storage_path($path = '')
{
    return App::getInstance()->storagePath($path);
}


/**
 * @param string $path
 * @return mixed
 */
function app_path($path = '')
{
    return App::getInstance()->appPath($path);
}


/**
 * @param string $path
 * @return mixed
 */
function public_path($path = '')
{
    return App::getInstance()->publicPath($path);
}


/**
 * @param string $path
 * @return mixed
 */
function path($path = '')
{
    return App::getInstance()->path($path);
}


/**
 * @param null $word
 * @param array $replace
 * @return mixed
 * @throws Exception
 */
function __($word = null, $replace = [])
{
    return lang($word, $replace);
}

/**
 * @param Int $http_code
 * @param string|object|null $exception
 * @param array $headers
 * @throws ReflectionException
 * @throws Exception
 */
function abort(int $http_code, string|object $exception = null, array $headers = [])
{
    $message = is_string($exception) ? $exception : $exception->getMessage();

    $file = app_path('Views/errors/' . $http_code);

    if (file_exists($file . '.blade.php')) {
        $content = view('errors.' . $http_code);
    } else if (file_exists($file . '.php')) {
        ob_start();
        import($file . '.php');
        $content = ob_get_clean();
    } else if (file_exists($file . '.html')) {
        $content = file_get_contents($file . '.html');
    } else {
        if (is_string($exception)) {
            $content = ['message' => $message];
        } else {
            $content = [
                'message' => $message,
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'code'    => $exception->getCode(),
                'trace'   => $exception->getTrace(),
            ];
        }

    }

    $response = App::get('response')->setStatusCode($http_code);

    $response->withHeaders($headers);

    $response->setContent($content);

    $response->send();

    App::end();
}


/**
 * @return bool
 */
function inConsole(): bool
{
    return CONSOLE;
}


/**
 * @return String
 * @throws Exception
 */
function csrf_token(): string
{
    static $token;

    if ($token === null) {
        $token = App::get('session')->get('_token');
    }

    return $token;
}


/**
 * @throws Exception
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '" />';
}


/**
 * @param string $name
 * @param array $parameters
 * @return mixed
 * @throws Exception
 */
function route(string $name, array $parameters = [])
{
    return App::get('route')->getName($name, $parameters);
}


if (!function_exists('flash')) {
    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    function flash(string $key): mixed
    {
        return App::get('session')->flash($key);
    }
}


if (!function_exists('is_base64')) {
    /**
     * @param string $string
     * @return bool
     */
    function is_base64(string $string): bool
    {
        return base64_encode(base64_decode($string)) === $string;
    }
}


if (!function_exists('response')) {
    /**
     * @return mixed
     * @throws Exception
     */
    function response(): Response
    {
        return App::get('response', ...func_get_args());
    }
}


if (!function_exists('json')) {
    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    function json($data)
    {
        return App::get('response')->json($data);
    }
}


if (!function_exists('report')) {
    /**
     * @param String $subject
     * @param String $message
     * @param null $destination
     * @return mixed
     * @throws Exception
     */
    function report(string $subject, string $message, $destination = null)
    {
        if (empty($destination)) {
            $destination = str_replace(' ', '-', $subject);
        }

        $logDir = path('storage/logs/');

        $extension = '.report';

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $logDir));
        }

        $report = '----------------------------' . PHP_EOL .
            ' Report                     ' . PHP_EOL .
            '----------------------------' . PHP_EOL .
            '|IP: ' . App::get('http')->ip() . PHP_EOL .
            '|Subject: ' . $subject . PHP_EOL .
            '|File: ' . debug_backtrace()[0]['file'] ?? '' . PHP_EOL .
            '|Line: ' . debug_backtrace()[0]['line'] ?? '' . PHP_EOL .
            '|Date: ' . strftime('%d %B %Y %H:%M:%S') . PHP_EOL .
            '|Message: ' . $message . PHP_EOL . PHP_EOL . PHP_EOL;
        return App::get('file')->append($logDir . $destination . $extension, $report);
    }
}


if (!function_exists('env')) {
    /**
     * @param $name
     * @return mixed
     */
    function env($name)
    {
        if (function_exists('getenv') && getenv($name)) {
            return getenv($name);
        }
        if (function_exists('apache_getenv') && apache_getenv($name)) {
            return apache_getenv($name);
        }

        return $_ENV[$name] ?? $_SERVER[$name] ?? null;
    }
}


if (!function_exists('cookie')) {
    /**
     * @return mixed
     * @throws Exception
     */
    function cookie()
    {
        if (func_num_args() === 0) {
            return App::get('cookie');
        }

        if (func_num_args() === 1) {
            return App::get('cookie')->get(func_get_arg(0));
        }

        return App::get('cookie', ...func_get_args());
    }
}


if (!function_exists('cache')) {
    /**
     * @return mixed
     * @throws Exception
     */
    function cache()
    {
        if (func_num_args() === 0) {
            return App::get('cache');
        }

        if (func_num_args() === 1) {
            return App::get('cache')->get(func_get_arg(0));
        }

        return App::get('cache')->put(...func_get_args());
    }
}


if (!function_exists('session')) {
    /**
     * @return mixed
     * @throws Exception
     */
    function session()
    {
        if (func_num_args() === 0) {
            return App::get('session');
        }

        if (func_num_args() === 1) {
            return App::get('session')->get(func_get_arg(0));
        }

        return App::get('session')->set(...func_get_args());
    }
}


if (!function_exists('view')) {
    /**
     * @param String $file
     * @param array $data
     * @param bool $cache
     * @return mixed
     * @throws Exception
     */
    function view(string $file, $data = [], $cache = false)
    {
        return App::get('view')->render($file, $data, $cache);
    }
}


if (!function_exists('redirect')) {
    /**
     * @param bool $link
     * @param int $refresh
     * @param int $http_response_code
     * @return mixed
     * @throws Exception
     */
    function redirect($link = false, $refresh = 0, $http_response_code = 302)
    {
        if ($link) {
            return App::get('redirect')->to($link, $refresh, $http_response_code);
        }

        return App::get('redirect');
    }
}


if (!function_exists('lang')) {
    /**
     * @param null $word
     * @param array $replace
     * @return mixed
     * @throws Exception
     */
    function lang($word = null, $replace = [])
    {
        if ($word !== null) {
            return App::get('translator')->translate($word, $replace);
        }

        return App::get('translator');
    }
}


if (!function_exists('validator')) {
    /**
     * @param null $data
     * @param array $rules
     * @return mixed
     * @throws Exception
     */
    function validator($data = null, $rules = [])
    {
        if ($data !== null) {
            return App::get('validator')->make($data, $rules);
        }

        return App::get('validator');
    }
}


if (!function_exists('get')) {
    /**
     * @param bool $name
     * @return mixed
     */
    function get($name = false)
    {
        return App::get('input')->get($name);
    }
}


if (!function_exists('post')) {
    /**
     * @param bool $name
     * @return mixed
     */
    function post($name = false)
    {
        return App::get('input')->post($name);
    }
}


if (!function_exists('request')) {
    /**
     * @return mixed
     */
    function request()
    {
        if (func_num_args() === 0) {
            return App::get('request');
        }

        if (func_num_args() === 1) {
            return App::get('request')->{func_get_arg(0)};
        }

        return App::get('request')->{func_get_arg(0)} = func_get_arg(1);
    }
}


if (!function_exists('xssClean')) {
    /**
     * @param $data
     * @return mixed
     */
    function xssClean($data)
    {
        return App::get('input')->xssClean($data);
    }
}


if (!function_exists('fullTrim')) {
    /**
     * @param $str
     * @param string $char
     * @return String
     */
    function fullTrim($str, $char = ' '): string
    {
        return str_replace($char, '', $str);
    }
}


if (!function_exists('encode_php_tag')) {
    /**
     * @param $str
     * @return String
     */
    function encode_php_tag($str): string
    {
        return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $str);
    }
}


if (!function_exists('preg_replace_array')) {
    /**
     * @param $pattern
     * @param array $replacements
     * @param $subject
     * @return String
     */
    function preg_replace_array($pattern, array $replacements, $subject): string
    {
        /**
         * @return mixed
         */
        $callback = static function () use (&$replacements) {
            return array_shift($replacements);
        };

        return preg_replace_callback($pattern, $callback, $subject);
    }
}


if (!function_exists('str_replace_first')) {
    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return String
     */
    function str_replace_first($search, $replace, $subject): string
    {
        return App::get('str')->replace_first($search, $replace, $subject);
    }
}


if (!function_exists('str_replace_last')) {
    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return String
     */
    function str_replace_last($search, $replace, $subject): string
    {
        return App::get('str')->replace_last($search, $replace, $subject);
    }
}


if (!function_exists('str_slug')) {
    /**
     * @param $str
     * @param string $separator
     * @return String
     */
    function str_slug($str, $separator = '-'): string
    {
        return App::get('str')->slug($str, $separator);
    }
}


if (!function_exists('str_limit')) {
    /**
     * @param $str
     * @param int $limit
     * @param string $end
     * @return String
     */
    function str_limit($str, $limit = 100, $end = '...'): string
    {
        return App::get('str')->limit($str, $limit, $end);
    }
}


if (!function_exists('upper')) {
    /**
     * @param String $str
     * @param string $encoding
     * @return String
     */
    function upper(string $str, $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($str, $encoding);
    }
}


if (!function_exists('lower')) {
    /**
     * @param String $str
     * @param string $encoding
     * @return String
     */
    function lower(string $str, $encoding = 'UTF-8'): string
    {
        return mb_strtolower($str, $encoding);
    }
}


if (!function_exists('title')) {
    /**
     * @param String $str
     * @param string $encoding
     * @return string
     */
    function title(string $str, $encoding = 'UTF-8'): string
    {
        return mb_convert_case($str, MB_CASE_TITLE, $encoding);
    }
}


if (!function_exists('len')) {
    /**
     * @param array|string $value
     * @param null|string $encoding
     * @return int|bool
     */
    function len($value, $encoding = null)
    {
        if (is_string($value)) {
            return mb_strlen($value, $encoding);
        }

        if (is_array($value)) {
            return count($value);
        }

        return 0;
    }
}


if (!function_exists('str_replace_array')) {
    /**
     * @param $search
     * @param array $replace
     * @param $subject
     * @return String
     */
    function str_replace_array($search, array $replace, $subject): string
    {
        return App::get('str')->replace_array($search, $replace, $subject);
    }
}


if (!function_exists('url')) {
    /**
     * @param null $url
     * @param array $parameters
     * @return mixed
     */
    function url($url = null, $parameters = [])
    {
        if ($url === null) {
            return App::get('url');
        }

        return App::get('url')->to(...func_get_args());
    }
}


if (!function_exists('current_url')) {
    /**
     * @param string $url
     * @return String
     */
    function current_url($url = ''): string
    {
        return App::get('url')->current($url);
    }
}


if (!function_exists('clean_url')) {
    /**
     * @param $url
     * @return String
     */
    function clean_url($url): string
    {
        if ($url === '') {
            return '';
        }

        $url = str_replace(array('http://', 'https://'), '', strtolower($url));

        if (strpos($url, 'www.') === 0) {
            $url = substr($url, 4);
        }
        $url = explode('/', $url);

        $url = reset($url);

        $url = explode(':', $url);

        $url = reset($url);

        return $url;
    }
}


if (!function_exists('segment')) {
    /**
     * @param Int $number
     * @return mixed
     * @throws Exception
     */
    function segment(int $number)
    {
        return App::get('url')->segment($number);
    }
}


if (!function_exists('debug')) {
    /**
     * @param $data
     */
    function debug($data)
    {
        ob_get_clean();
        echo '<pre style="background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000">';

        if (is_array($data)) {
            print_r($data);
        } else {
            var_dump($data);
        }
        echo '</pre>';
        die();
    }
}


if (!function_exists('is_mail')) {
    /**
     * @param String $mail
     * @return mixed
     * @throws Exception
     */
    function is_mail(string $mail)
    {
        return App::get('validator')->is_mail($mail);
    }
}


if (!function_exists('is_url')) {
    /**
     * @param String $url
     * @return mixed
     */
    function is_url(string $url)
    {
        return App::get('validator')->is_url($url);
    }
}


if (!function_exists('is_ip')) {
    /**
     * @param $ip
     * @return mixed
     */
    function is_ip($ip)
    {
        return App::get('validator')->is_ip($ip);
    }
}


if (!function_exists('css')) {
    /**
     * @param $file
     * @param bool $modifiedTime
     * @return String
     */
    function css($file, $modifiedTime = false): string
    {
        return App::get('html')->css($file, $modifiedTime);
    }
}


if (!function_exists('js')) {
    /**
     * @param $file
     * @param bool $modifiedTime
     * @return String
     */
    function js($file, $modifiedTime = false): string
    {
        return App::get('html')->js($file, $modifiedTime);
    }
}


if (!function_exists('img')) {
    /**
     * @param $file
     * @param array $attributes
     * @return String
     */
    function img($file, $attributes = []): string
    {
        return App::get('html')->img($file, $attributes);
    }
}
