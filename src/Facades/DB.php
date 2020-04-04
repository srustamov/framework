<?php

namespace TT\Facades;

/**
 * @package	TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 */

use TT\Engine\App;

/**
 * @method static table(string $table): \TT\Database\Builder
 * @method static where($column,$operator = null, $value = null): \TT\Database\Builder
 * @method static first()
 * @method static get($first = false,$fetch_style = \PDO::FETCH_OBJ)
 * @method static exec(string $query)
 * @method static setModel(\TT\Database\Orm\Model $getInstance): \TT\Database\Builder
 * @method static pdo(string $string = null)
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
