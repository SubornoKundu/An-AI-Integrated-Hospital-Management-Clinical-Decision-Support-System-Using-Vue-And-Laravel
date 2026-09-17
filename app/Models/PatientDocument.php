<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    protected $fillable = ['patient_id','appointment_id','file_name','file_path','file_type','doc_type','notes'];

    public function patient()     { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
