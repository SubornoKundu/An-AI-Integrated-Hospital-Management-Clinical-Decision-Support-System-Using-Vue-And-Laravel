<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HospitalSetting extends Model
{
    protected $primaryKey = 'skey';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['skey','sval'];

    // Read on every page load via HandleInertiaRequests (up to 5 keys per
    // request: logo, name, address, phone, email) for data that only
    // changes when an admin edits hospital settings — a clear cache
    // candidate. set() is the only place any of these get written, so
    // invalidation below is the only place that needs to happen.
    public static function get(string $key, string $default = ''): string
    {
        return Cache::remember("hospital_setting:{$key}", 3600, fn () => static::find($key)?->sval ?? $default);
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['skey' => $key], ['sval' => $value]);
        Cache::forget("hospital_setting:{$key}");
    }
}
