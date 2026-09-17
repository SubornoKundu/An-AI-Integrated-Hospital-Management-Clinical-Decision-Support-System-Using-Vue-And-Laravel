<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Patient extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'contact', 'photo',
        'password', 'gender', 'date_of_birth', 'blood_group', 'address',
        'known_allergies', 'chronic_conditions', 'current_medications', 'family_medical_history',
        'last_seen_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_seen_at' => 'datetime',
    ];

    // ── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    // ── Relationships ────────────────────────────────────────────

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function favourites()
    {
        return $this->hasMany(PatientFavourite::class);
    }

    public function favouriteDoctors()
    {
        return $this->belongsToMany(Doctor::class, 'patient_favourites');
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id')
                    ->where('user_role', 'patient');
    }
}
