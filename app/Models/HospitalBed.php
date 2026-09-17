<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalBed extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id', 'bed_number', 'bed_type', 'status', 'daily_charge', 'notes',
    ];

    // ── Relationships ────────────────────────────────────────────
    public function ward()
    {
        return $this->belongsTo(HospitalWard::class, 'ward_id');
    }

    public function admissions()
    {
        return $this->hasMany(BedAdmission::class, 'bed_id');
    }

    public function currentAdmission()
    {
        return $this->hasOne(BedAdmission::class, 'bed_id')->where('status', 'admitted');
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'available'   => 'success',
            'occupied'    => 'danger',
            'maintenance' => 'warning',
            'reserved'    => 'info',
            default       => 'secondary',
        };
    }
}
