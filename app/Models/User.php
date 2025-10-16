<?php

namespace App\Models;

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

    ];
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'restorable_until'  => 'datetime',
    ];
    protected $appends = ['image_url'];
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
    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class,'user_id');
    }

    public function routeNotificationForFcm(): array {
        return $this->deviceTokens()->pluck('token')->all();
    }

}
