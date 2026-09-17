<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'appointment_id','patient_id','doctor_id',
        'diagnosis','medicines','instructions','follow_up_date','safety_check_override',
    ];

    protected $casts = ['follow_up_date' => 'date'];

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function doctor()      { return $this->belongsTo(Doctor::class)->withTrashed(); }
}
