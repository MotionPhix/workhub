<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class WorkEntry extends Model
{
  use HasFactory, BootUuid, HasTags;

  protected $fillable = [
    'user_id',
    'work_date',
    'description',
    'hours_worked',
    'status'
  ];

  protected $casts = [
    'work_date' => 'date:Y-m-d',
    'tags' => 'array'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
