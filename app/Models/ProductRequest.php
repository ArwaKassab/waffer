<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductRequest extends Model
{
    protected $table = 'product_change_requests';
    protected $guarded = [];

    protected $fillable = [
        'action','product_id','store_id','status',
        'name','price','status_value','quantity','unit','image',
        'product_updated_at_snapshot','review_note', 'details',
    ];


    /**
     * المنتج المرتبط بالطلب (للـ update فقط).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * المتجر (User) المرتبط بالطلب (للـ create فقط).
     */
    public function store()
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    protected $appends = ['image_url'];
    protected $hidden  = ['image'];

    // خزّني المسار النسبي فقط
    public function setImageAttribute($value): void
    {
        if (!$value) { $this->attributes['image'] = null; return; }

        if (preg_match('#^https?://#i', $value)) {
            $value = parse_url($value, PHP_URL_PATH) ?? $value;
        }

        $path = ltrim(preg_replace('#^/?storage/#', '', $value), '/');
        $this->attributes['image'] = $path; // مثال: product-requests/xxx.jpg
    }

    // رجّعي الرابط الكامل
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
