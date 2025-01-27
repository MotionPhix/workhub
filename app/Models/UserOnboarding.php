<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnboarding extends Model
{
  // Define the table associated with the model
  protected $table = 'user_onboardings';

  // Specify the attributes that are mass assignable
  protected $fillable = [
    'user_id',
    'step',
    'completed_at',
  ];

  // Define a relationship to the User model
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  // Check if the onboarding is complete
  public function isComplete()
  {
    return !is_null($this->completed_at);
  }

  // Mark the onboarding as complete
  public function complete()
  {
    $this->completed_at = now();
    $this->save();
  }
}
