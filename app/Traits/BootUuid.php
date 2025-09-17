<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait BootUuid
{
    public static function bootBootUuid()
    {
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });

        static::updating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
