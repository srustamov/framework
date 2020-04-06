<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @method static importRouteFiles(array $files)
 * @method static getRoutes()
 * @method static getCurrent(): \TT\Engine\Http\Route
 * @method static setRoutes(array $routes)
 * @method static getName($name, array $parameters)
 * @method static get($path, $handler): self
 * @method static post($path, $handler): self
 * @method static put($path, $handler): self
 * @method static delete($path, $handler): self
 * @method static options($path, $handler): self
 * @method static patch($path, $handler): self
 * @method static form($path, $handler): self
 * @method static any($path, $handler): self
 * @method static group($group_parameters, \Closure $callback)
 * @method static name($name): self
 * @method static pattern($pattern): self
 * @method static middleware($middleware): self
 * @method static domain($domain): self
 * @method static prefix($prefix): self
 * @method static run()
 */




class Route extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'route';
    }
}
