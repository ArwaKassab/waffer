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
        'firebase_uid',
        'user_name',

    ];
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'restorable_until'  => 'datetime',
        'status'            => 'boolean',
    ];
    protected $appends = ['image_url', 'is_open_now', 'open_hour_formatted', 'close_hour_formatted','phone_display'];
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

    public function getIsOpenNowAttribute(): bool
    {
        if (! $this->status) {
            return false;
        }

        if (! $this->open_hour || ! $this->close_hour) {
            return true;
        }

        $tz  = config('app.timezone', 'Asia/Damascus');
        $now = Carbon::now($tz);

        $from = Carbon::parse($this->open_hour, $tz)->setDate($now->year, $now->month, $now->day);
        $to   = Carbon::parse($this->close_hour, $tz)->setDate($now->year, $now->month, $now->day);

        if ($from->lte($to)) {
            return $now->between($from, $to, true); // inclusive
        }

        return $now->gte($from) || $now->lt($to);
    }




    public function getOpenHourFormattedAttribute(): ?string
    {
        return $this->open_hour
            ? Carbon::parse($this->open_hour)->format('H:i')
            : null;
    }

    public function getCloseHourFormattedAttribute(): ?string
    {
        return $this->close_hour
            ? Carbon::parse($this->close_hour)->format('H:i')
            : null;
    }

    public function getPhoneDisplayAttribute(): string
    {
        if (str_starts_with($this->phone, '00963')) {
            return '0' . substr($this->phone, 5);
        }

        return $this->phone;
    }

}
