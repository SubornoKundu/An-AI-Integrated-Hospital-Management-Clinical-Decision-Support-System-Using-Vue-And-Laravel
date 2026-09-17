<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    public $timestamps = false;
    protected $fillable = ['doctor_id','day_of_week','start_time','end_time','is_active'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public static $days = [0=>'Sunday',1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday'];
}
