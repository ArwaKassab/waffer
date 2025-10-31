<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUserNotification extends Model
{
    protected $table = 'app_user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'order_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // ترجع علاقة الطلب أو المستخدم بالمستقبل:
    // public function user() { return $this->belongsTo(User::class); }
    // public function order() { return $this->belongsTo(Order::class); }
}
