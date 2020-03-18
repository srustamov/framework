<?php


namespace TT\Libraries\Database\Relations;

trait BelongsTo
{
    public function belongsTo(string $model, $foreing_key)
    {
        return $model::find($this[$foreing_key]);
    }
}
