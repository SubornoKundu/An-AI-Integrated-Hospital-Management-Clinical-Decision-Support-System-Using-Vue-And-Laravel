<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StaffAdmin extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name','email','username','password','role','is_active','photo','gender'];
    protected $hidden = ['password','remember_token'];
}
