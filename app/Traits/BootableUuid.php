<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait BootableUuid
{
  public static function bootBootableUuid()
  {
    static::creating(function ($model) {
      $model->uuid = Str::orderedUuid();
    });

    static::updating(function ($model) {
      if (! $model->uuid) $model->uuid = Str::orderedUuid();
    });
  }
}
