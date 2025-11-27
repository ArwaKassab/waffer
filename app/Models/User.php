<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'whatsapp_phone',
        'password',
        'area_id',
        'email',
        'image',
        'open_hour',
        'close_hour',
        'status',
        'wallet_balance',
        'type',
        'note',
        'phone_shadow',
        'restorable_until',
//        'user_name',

    ];
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'restorable_until'  => 'datetime',
    ];
    protected $appends = ['image_url', 'is_open_now'];
    protected $hidden  = ['image'];

    public function setImageAttribute($value): void
    {
        if (!$value) { $this->attributes['image'] = null; return; }

        if (preg_match('#^https?://#i', $value)) {
            $value = parse_url($value, PHP_URL_PATH) ?? $value;
        }

        $path = ltrim(preg_replace('#^/?storage/#', '', $value), '/');
        $this->attributes['image'] = $path;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'store_category', 'store_id', 'category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function productChangeRequests()
    {
        return $this->hasMany(ProductRequest::class, 'store_id');
    }
    public function deviceTokens()
    {
        return $this->hasMany(\App\Models\DeviceToken::class, 'user_id');
    }

    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->all();
    }

    /**
     * هل المتجر مفتوح الآن حسب الحالة وساعات العمل؟
     */
    public function getIsOpenNowAttribute(): bool
    {
        // لو مش متجر أصلاً
        if ($this->type !== 'store') {
            return false;
        }

        // لو المتجر معطّل أو محظور نعتبره مغلق دائماً
        if (!$this->status || $this->is_banned) {
            return false;
        }

        // لو ما عنده ساعات عمل مضبوطة نرجّع status
        if (!$this->open_hour || !$this->close_hour) {
            return (bool) $this->status;
        }

        $now  = Carbon::now(config('app.timezone'))->format('H:i:s');
        $from = $this->open_hour;
        $to   = $this->close_hour;

        // حالة طبيعية: 08:00 -> 22:00
        if ($from <= $to) {
            return $now >= $from && $now < $to;
        }

        // حالة تمتد بعد منتصف الليل: 20:00 -> 02:00
        return $now >= $from || $now < $to;
    }

}
