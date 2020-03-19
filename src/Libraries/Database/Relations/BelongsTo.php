<?php


namespace TT\Libraries\Database\Relations;


trait BelongsTo
{
    public function belongsTo($model, $foreign_key)
    {
        /**@var $model \TT\Libraries\Database\Model*/
        return $model::find($this[$foreign_key]);
    }
}
