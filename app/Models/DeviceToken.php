<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = ['user_id','token','platform','device_name','last_used_at'];
    protected $casts = ['last_used_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
}
