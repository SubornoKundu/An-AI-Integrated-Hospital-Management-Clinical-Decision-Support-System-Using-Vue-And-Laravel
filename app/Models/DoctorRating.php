<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRating extends Model
{
    protected $fillable = ['doctor_id','patient_id','appointment_id','rating','review'];

    public function doctor()      { return $this->belongsTo(Doctor::class)->withTrashed(); }
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
