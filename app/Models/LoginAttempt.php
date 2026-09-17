<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;
    protected $fillable = ['identifier', 'role', 'ip'];
    protected $casts    = ['attempt_time' => 'datetime'];
}
