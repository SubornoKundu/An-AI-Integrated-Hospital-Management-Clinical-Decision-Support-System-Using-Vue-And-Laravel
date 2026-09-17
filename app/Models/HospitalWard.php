<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalWard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'total_beds', 'floor', 'building', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // ── Relationships ────────────────────────────────────────────
    public function beds()
    {
        return $this->hasMany(HospitalBed::class, 'ward_id');
    }

    // ── Computed helpers ─────────────────────────────────────────
    public function getAvailableBedsCountAttribute(): int
    {
        return $this->beds()->where('status', 'available')->count();
    }

    public function getOccupiedBedsCountAttribute(): int
    {
        return $this->beds()->where('status', 'occupied')->count();
    }

    public function getOccupancyRateAttribute(): float
    {
        $total = $this->beds()->count();
        if ($total === 0) return 0;
        return round(($this->occupiedBedsCount / $total) * 100, 1);
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
