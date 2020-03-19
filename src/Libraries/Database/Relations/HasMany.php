<?php namespace TT\Libraries\Database\Relations;

trait HasMany
{
    public function hasMany($model, $foreign_key)
    {
        /**@var $model \TT\Libraries\Database\Model*/
        return $model::where($foreign_key, $this[$this->primaryKey]);
    }
}
