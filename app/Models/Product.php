<?php

namespace App\Models;
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
        return $this->hasOne(Discount::class);
    }


    public function activeDiscount() {
        return $this->hasOne(Discount::class)
            ->where('status','active')
            ->whereDate('start_date','<=',now())
            ->whereDate('end_date','>=',now())
            ->latestOfMany('start_date');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_product', 'product_id', 'offer_id');
    }

    public function activeDiscountToday()
    {
        $today = Carbon::today()->toDateString();

        return $this->discounts()
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    public function changeRequests()
    {
        return $this->hasMany(ProductRequest::class, 'product_id');
    }




}
