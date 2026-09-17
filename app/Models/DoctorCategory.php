<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'description', 'is_active'];

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
