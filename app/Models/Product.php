<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'price',
        'image',
        'status',
        'quantity',
        'unit',
        'store_id',
        'details',

    ];

    public const UNITS = ['غرام', 'كيلوغرام', 'قطعة', 'لتر'];
    protected $appends = ['image_url'];
    protected $hidden  = ['image'];
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function setImageAttribute($value): void
    {
        if (!$value) { $this->attributes['image'] = null; return; }

        if (preg_match('#^https?://#i', $value)) {
            $value = parse_url($value, PHP_URL_PATH) ?? $value;
        }

        $path = ltrim(preg_replace('#^/?storage/#', '', $value), '/');
        $this->attributes['image'] = $path;
    }

    public function store()
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_items')
            ->withPivot('quantity')->withTimestamps();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'price', 'store_id')->withTimestamps();
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

//    public function activeDiscount(): HasOne
//    {
//        $today = Carbon::now(config('app.timezone'))->toDateString();
//
//        return $this->hasOne(Discount::class)
//            ->where('discounts.status', 'active')
//            ->whereDate('discounts.start_date', '<=', $today)
//            ->where(function ($q) use ($today) {
//                $q->whereDate('discounts.end_date', '>=', $today)
//                    ->orWhereNull('discounts.end_date');
//            })
//            ->latestOfMany('start_date');
//    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_product', 'product_id', 'offer_id');
    }

    public function activeDiscount(): HasOne
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        return $this->hasOne(Discount::class)
            ->where('discounts.status', 'active')
            ->whereDate('discounts.start_date', '<=', $today)
            ->whereDate('discounts.end_date', '>=', $today)
            ->latestOfMany('start_date');
    }




    public function changeRequests()
    {
        return $this->hasMany(ProductRequest::class, 'product_id');
    }




}
