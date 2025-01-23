<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
  use HasFactory, BootableUuid;

  protected $fillable = ['name', 'description', 'uuid'];

  public function users()
  {
    return $this->hasMany(User::class);
  }
}
