<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait BootableUuid
{
    public static function bootBootableUuid()
    {
        static::creating(function ($model) {
            // Check if the model has a uuid column, otherwise use id
            $uuidField = $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'uuid') ? 'uuid' : 'id';

            if (empty($model->$uuidField)) {
                $model->$uuidField = Str::orderedUuid();
            }
        });

        static::updating(function ($model) {
            // Check if the model has a uuid column, otherwise use id
            $uuidField = $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'uuid') ? 'uuid' : 'id';

            if (empty($model->$uuidField)) {
                $model->$uuidField = Str::orderedUuid();
            }
        });
    }
}
