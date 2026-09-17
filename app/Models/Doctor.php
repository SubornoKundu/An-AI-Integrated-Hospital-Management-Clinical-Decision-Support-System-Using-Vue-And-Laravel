<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Doctor extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'category_id',
        'consultancy_fee', 'bio', 'phone', 'is_active', 'photo', 'gender',
        'last_seen_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'consultancy_fee' => 'decimal:2',
        'last_seen_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(DoctorCategory::class, 'category_id');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function breaks()
    {
        return $this->hasMany(DoctorBreak::class);
    }

    public function blockedDates()
    {
        return $this->hasMany(DoctorBlockedDate::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function templates()
    {
        return $this->hasMany(PrescriptionTemplate::class);
    }

    public function ratings()
    {
        return $this->hasMany(DoctorRating::class);
    }

    public function referralsSent()
    {
        return $this->hasMany(Referral::class, 'from_doctor_id');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAvgRatingAttribute(): float
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }

    public function getRatingCountAttribute(): int
    {
        return $this->ratings()->count();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
