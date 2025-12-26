<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'app_key',
        'package_name',
        'device_type',
        'app_version',
        'last_used_at',
        'visitor_id',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];
}
