<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @method static table($table): self
 * @method static where(): self
 * @method static first()
 * @method static get()
 * @method static exec(string $query)
 * @method static check()
 */
use TT\Engine\App;

class DB extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'database';
    }

    public static function check()
    {
      return App::get('database',true)->pdo ?? false;
    }
}
