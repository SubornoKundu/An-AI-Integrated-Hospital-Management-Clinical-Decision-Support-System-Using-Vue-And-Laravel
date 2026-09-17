<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id','user_role','title','message','type','is_read','link'];

    public function scopeUnread($q)  { return $q->where('is_read',false); }
    public function scopeForUser($q,$id,$role) {
        return $q->where('user_id',$id)->where('user_role',$role);
    }
}
