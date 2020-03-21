<?php namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 */
use TT\Engine\App;

/**
 * @method static table($table): self
 * @method static where(): self
 * @method static first()
 * @method static get()
 * @method static exec(string $query)
 * @method static setModel(\TT\Libraries\Database\Model $getInstance): self
 */
class DB extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'database';
    }

    public static function check()
    {
        return App::get('database', true)->pdo ?? false;
    }
}
