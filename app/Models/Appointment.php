<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id','doctor_id','appointment_date',
        'slot_start','slot_end','status','visit_notes','consultancy_fee',
    ];

    // Custom-format date cast (NOT plain 'date') is required here.
    // The plain 'date' cast still stores through fromDateTime(), which formats
    // using the connection's default date format ('Y-m-d H:i:s') unless
    // $dateFormat is overridden — so `Appointment::create(['appointment_date' => '2026-08-16'])`
    // was actually saving '2026-08-16 00:00:00'. MySQL quietly truncates that
    // extra time part when it lands in a DATE column, which is why this never
    // surfaced against the production database — but SQLite (used by the test
    // suite) has no column-type coercion and stores the string exactly as given.
    // Every exact-match `where('appointment_date', $date)` — including the
    // "is this slot already booked" check in SlotService — then compared
    // '2026-08-16' against the stored '2026-08-16 00:00:00' and never matched,
    // so existing bookings were invisible to both the app and the tests.
    // 'date:Y-m-d' stores the plain string as-is (no reformatting on write)
    // while still returning a normal Carbon instance on read, so every
    // ->format(...) call site elsewhere in the app is unaffected.
    protected $casts = ['appointment_date' => 'date:Y-m-d'];

    public function patient()      { return $this->belongsTo(Patient::class); }
    public function doctor()       { return $this->belongsTo(Doctor::class)->withTrashed(); }
    public function prescription() { return $this->hasOne(Prescription::class); }
    public function vital()        { return $this->hasOne(Vital::class); }
    public function rating()       { return $this->hasOne(DoctorRating::class); }
    public function chatMessages() { return $this->hasMany(ChatMessage::class); }
    public function referral()     { return $this->hasOne(Referral::class); }
    public function documents()    { return $this->hasMany(PatientDocument::class); }

    public function scopeActive($q)    { return $q->where('status','active'); }
    public function scopeCompleted($q) { return $q->where('status','completed'); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active'               => '<span class="badge badge-success">● Active</span>',
            'completed'            => '<span class="badge" style="background:rgba(79,142,247,.12);color:#4f8ef7;border:1px solid rgba(79,142,247,.2)">✓ Completed</span>',
            'cancelled_by_patient' => '<span class="badge badge-danger">✕ Cancelled</span>',
            'cancelled_by_doctor'  => '<span class="badge badge-warning">✕ Cancelled by Doctor</span>',
            default                => '<span class="badge">'.$this->status.'</span>',
        };
    }
}
