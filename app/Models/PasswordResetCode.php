<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    protected $fillable = ['email', 'role', 'code', 'attempts', 'verified', 'expires_at'];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
