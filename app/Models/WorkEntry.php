<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkEntry extends Model
{
  use HasFactory, BootUuid;

  protected $fillable = [
    'user_id',
    'work_date',
    'description',
    'hours_worked',
    'tags',
    'status'
  ];

  protected $casts = [
    'work_date' => 'date',
    'tags' => 'array'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
