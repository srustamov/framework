<?php

namespace TT\Libraries\Database;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */



use TT\Facades\DB;
use ArrayAccess;
use JsonSerializable;
use Countable;
use TT\Libraries\Database\Relations\Relation;


/**
 * @method static where($foreign_key, $primaryKey):Database
 * @method static get($first)
 * @method static create($data)
 * @method static save()
 * @method static delete()
 * @method static destroy($key)
 * @method static first()
 * @method static find($primaryKey)
 * @method static findOrFail($primaryKey)
 */
abstract class Model implements ArrayAccess, JsonSerializable, Countable
{

    private static $models = [];

    private $attributes = [];

    protected $table;

    protected $primaryKey = 'id';

    public $eager = [];


    /**
     * Model constructor.
     */
    public function __construct()
    {
        if (!$this->isBooted()) {
            $this->boot();
        }
    }


    protected function boot()
    {
        if ($this->table === null) {
            $called_class = explode('\\', static::class);
            $this->table = strtolower(array_pop($called_class)) . 's';
        }
        if ($this->primaryKey === null) {
            $this->primaryKey = 'id';
        }

        self::$models[static::class] = [
            'table' => $this->table,
            'primaryKey' => $this->primaryKey
        ];
    }


    /**
     * @return bool
     */
    protected function isBooted(): bool
    {
        return isset(self::$models[static::class]);
    }


    /**
     * @param $key
     * @return mixed|Model
     */
    public function setPrimaryKey($key)
    {
        self::$models[static::class]['primaryKey'] = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return self::$models[static::class]['primaryKey'];
    }


    /**
     * @return mixed
     */
    public function getTable()
    {
        return self::$models[static::class]['table'];
    }


    /**
     * @param $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        if (method_exists($this,$name)) {
            $relation = $this->$name();
            if($relation instanceof Relation) {
                return $this->$name()->_load();
            }
            return $relation;
        }
        return null;

    }

    /**
     * @param $name
     * @param $value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param $attributes
     * @return mixed|Model
     */
    public function setAttributes($attributes)
    {
        $this->attributes = (array) $attributes;

        return $this;
    }


    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Database
     */
    public function getQuery(): Database
    {
        return DB::setModel($this)->table($this->getTable());
    }


    /**
     * @return ModelBuilder
     */
    public function getBuilder(): ModelBuilder
    {
        return new ModelBuilder($this);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    private function callCustomMethod($name, $arguments)
    {
        return $this->getBuilder()->{$name}(...$arguments);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->callCustomMethod($name, $arguments);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return (new static)->$name(...$arguments);
    }


    /**
     * @param $column
     * @param $value
     */
    public function __set($column, $value)
    {
        $this->setAttribute($column, $value);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->getAttributes());
    }

    /**
     * @param $column
     * @return array|null
     */
    public function __get($column)
    {
        return $this->getAttribute($column);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->getAttributes());
    }

    /**
     * Clone Model Class
     */
    public function __clone()
    {
        $this->setAttributes([]);
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
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
        return $this->getAttribute($offset);
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
        $this->setAttribute($offset, $value);
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
        if (array_key_exists($offset, $this->getAttributes())) {
            unset($this->attributes[$offset]);
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
        return array_key_exists($offset, $this->getAttributes());
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return array The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function jsonSerialize(): array
    {
        return $this->getAttributes();
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
    public function count():int
    {
        return count($this->getAttributes());
    }
}
