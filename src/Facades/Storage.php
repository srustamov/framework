<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * 
 * @method static put(String $path, $content)
 * @method static prepend(String $file, $content)
 * @method static get($file, callable $callback = null)
 * @method static exists($file): bool
 * @method static append(String $file, $content)
 * @method static directories($path): array
 * @method static allDirectories($path)
 * @method static allFiles($path): array
 * @method static files($path): array
 * @method static delete($file)
 * @method static rmdir($directories)
 * @method static size($file)
 * @method static copy($source, $copy)
 * @method static move($source, $copy)
 * @method static mkdir($dir, $mode = 0777)
 * @method static touch($file)
 * @method static modifiedTime($file)
 * @method static path($path)
 */


class Storage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'storage';
    }
}
