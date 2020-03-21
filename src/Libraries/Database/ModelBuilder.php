<?php


namespace TT\Libraries\Database;


use RuntimeException;
use TT\Libraries\Arr;
use App\Exceptions\ModelNotFoundException;
use TT\Libraries\Database\Relations\BelongsTo;
use TT\Libraries\Database\Relations\HasMany;

class ModelBuilder
{

    protected $model;

    protected $query;


    /**
     * ModelBuilder constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->query = $model->getQuery();

        $this->model = $model;
    }


    /**
     * @param $child
     * @param $key
     * @return BelongsTo
     */
    public function belongsTo($child, $key):BelongsTo
    {
        return new BelongsTo($child, $key, $this->model);
    }

    /**
     * @param $child
     * @param $key
     * @return HasMany
     */
    public function hasMany($child, $key):HasMany
    {
        return new HasMany($child,$key,$this->model);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->model->getAttribute($name);
    }


    /**
     * @return bool
     */
    public function save(): Bool
    {
        if (!empty($this->model->getAttributes())) {
            $pk = $this->model->getPrimaryKey();
            if (array_key_exists($pk, $this->model->getAttributes())) {
                return $this->query
                    ->where($pk, $this->model->getAttribute($pk))
                    ->update(Arr::except($this->model->getAttributes(), [$pk]));
            }
            $this->create($this->model->getAttributes());
        }
        return false;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        if ($key = $this->model->getAttribute($this->model->getPrimaryKey())) {
            $delete = $this->destroy($key);
            $this->model->setAttributes([]);
        }
        return $delete ?? false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function create(array $data): Bool
    {
        return $this->query->insert($data);
    }


    /**
     * @param array|int $primaryKey
     * @return mixed
     * @internal param $pk
     */
    public function find($primaryKey)
    {
        $pk = $this->model->getPrimaryKey();

        if ($pk === null) {
            throw new RuntimeException('No primary key defined on model.');
        }
        if (is_array($primaryKey)) {
            if (Arr::isAssoc($primaryKey)) {
                $where = $primaryKey;
            } else {
                return $this->query->whereIn($pk,$primaryKey)->get();
            }
        } else {
            $where = [$pk => $primaryKey];
        }

        return $this->query->where($where)->first();
    }


    /**
     * @param mixed ...$args
     * @return mixed
     * @throws \Exception
     */
    public function findOrFail(...$args)
    {
        if ($model = $this->find(...$args)) {
            return $model;
        }
        if (class_exists(ModelNotFoundException::class)) {
            throw new ModelNotFoundException;
        }
        abort(404);
    }


    /**
     * @param $primaryKey
     * @return bool
     */
    public function destroy($primaryKey):bool
    {
        $pk = $this->model->getPrimaryKey();

        if ($pk === null) {
            throw new RuntimeException('No primary key defined on model.');
        }
        /**@var $query Database */

        if (is_array($primaryKey)) {
            $this->query->whereIn($pk, $primaryKey);
        } else {
            $this->query->where($pk, $primaryKey);
        }

        return $query->delete();
    }


    /**
     * @param $relations
     * @return mixed|Model
     */
    public function load($relations)
    {
        if (is_array($relations)) {
            $this->model->eager = array_merge($this->model->eager, $relations);
        } else {
            $this->model->eager[] = $relations;
        }
        return $this;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if(stripos($name, 'findby') === 0) {
            $column = substr($name,6);
            if($column !== '') {
                return $this->find([$column => $arguments[0] ?? null]);
            }
        }

        return $this->query->$name(...$arguments);
    }


}