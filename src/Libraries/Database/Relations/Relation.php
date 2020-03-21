<?php


namespace TT\Libraries\Database\Relations;


abstract class Relation
{

    /**
     * @return mixed
     */
    abstract public function getKey();


    /**
     * @return mixed
     */
    abstract public function getForeignKey();


    /**
     * @return mixed
     */
    abstract public function _load();


    /**
     * @param $result
     * @param string $attribute_name
     * @return mixed
     */
    abstract public function getResult($result,string $attribute_name);

}
