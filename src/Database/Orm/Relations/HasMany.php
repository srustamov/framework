<?php

namespace TT\Database\Orm\Relations;

use TT\Database\Orm\Model;

class HasMany extends Relation
{
    private $abstract;

    private $model;

    private $key;

    private $foreignKey;

    private $values;

    /**
     * HasMany constructor.
     * @param $child
     * @param $key
     * @param $abstract
     */
    public function __construct($child, $key, Model $abstract)
    {
        $this->model = new $child;

        $this->foreignKey = $abstract->getPrimaryKey();

        $this->key = $key;

        $this->abstract = $abstract;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setKeyValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @return mixed
     */
    public function _load()
    {
        if ($this->values === null) {
            $result = $this->model->where($this->key, $this->abstract[$this->foreignKey]);
        } else {
            $result = $this->model->whereIn($this->key, $this->values);
        }

        $this->result = false;
        $this->values = [];
        $this->model = null;

        return $result->get();
    }


    /**
     * @param $result
     * @param string $attribute_name
     * @return mixed
     */
    public function getResult($result, string $attribute_name)
    {
        $this->setKeyValues(
            array_values(array_unique(
                array_column((array) $result, $this->foreignKey)
            ))
        );
        $key = $this->getKey();
        $foreign = $this->foreignKey;
        $eager_result = $this->_load();
        $column_value = null;

        if (is_array($result)) {
            foreach ($result as $item) {
                foreach ($eager_result ?? [] as $data) {
                    if ($item->getAttribute($foreign) === $data[$key]) {
                        (array) $column_value[] = $data;
                    }
                }
                $item->setAttribute($attribute_name, $column_value);
                $column_value = null;
            }
        } else {
            foreach ($eager_result as $data) {
                if ($result->getAttribute($foreign) === $data[$key]) {
                    (array) $column_value[] = $data;
                }
            }
            $result->setAttribute($attribute_name, $column_value);
        }

        return $result;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->model = $this->model->{$name}(...$arguments);

        return $this;
    }
}
