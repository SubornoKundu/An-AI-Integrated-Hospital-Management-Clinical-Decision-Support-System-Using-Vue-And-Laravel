<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = ['from_doctor_id','to_category_id','patient_id','appointment_id','reason','notes','status'];

    public function fromDoctor()  { return $this->belongsTo(Doctor::class,'from_doctor_id')->withTrashed(); }
    public function toCategory()  { return $this->belongsTo(DoctorCategory::class,'to_category_id'); }
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
