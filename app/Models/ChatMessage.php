<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public $timestamps = false;
    protected $fillable = ['appointment_id','sender_role','sender_id','message','is_read','sent_at'];
    protected $casts    = ['sent_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function($m) {
            if (empty($m->sent_at)) $m->sent_at = now();
        });
    }

    public function appointment() { return $this->belongsTo(Appointment::class); }
}
