<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorBreak extends Model
{
    public $timestamps = false;
    protected $fillable = ['doctor_id','day_of_week','break_start','break_end','label'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
