<?php


namespace TT\Libraries\Database\Relations;



use TT\Libraries\Database\Model;

class BelongsTo extends Relation
{
    /**@var $query Model*/
    private $model;

    /**@var $query Model*/
    private $abstract;

    private $result = false;

    private $key;

    private $foreignKey;

    private $values;


    /**
     * BelongsTo constructor.
     * @param $model
     * @param $key
     * @param Model $abstract
     */
    public function __construct($model, $key, Model $abstract)
    {
        $this->model = new $model;

        $this->key = $this->model->getPrimaryKey();

        $this->foreignKey = $key;

        $this->abstract = $abstract;

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
    public function _load()
    {
        if ($this->values === null) {
            $this->values = $this->abstract[$this->getForeignKey()];
        }

        $result = $this->model->find($this->values);

        $this->result = false;

        $this->model = null;

        return $result;
    }

    /**
     * @param $result
     * @param string $attribute_name
     * @return mixed
     */
    public function getResult($result,string $attribute_name)
    {
        $this->setKeyValues(
            array_values(array_unique(
                array_column((array) $result,$this->foreignKey)
            ))
        );
        $key = $this->getKey();
        $foreign = $this->foreignKey;
        $eager_result = $this->_load();
        $column_value = null;

        if (is_array($result)) {
            foreach ($result as $item) {
                foreach ((array) $eager_result as $data) {
                    if ($item->getAttribute($foreign) === $data[$key]) {
                        $column_value = $data;
                        break;
                    }
                }
                $item->setAttribute($attribute_name,$column_value);
                $column_value = null;
            }
        } else {
            $result->setAttribute($attribute_name,$eager_result);
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

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if($this->result === false) {
            $this->result = $this->model->first();
        }
        return $this->result->{$name} ?? null;
    }


}
