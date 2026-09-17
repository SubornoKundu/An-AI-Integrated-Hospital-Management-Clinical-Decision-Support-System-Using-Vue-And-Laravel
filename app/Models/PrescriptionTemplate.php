<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionTemplate extends Model
{
    protected $fillable = ['doctor_id','template_name','diagnosis','medicines','instructions'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
