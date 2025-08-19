<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRequest extends Model
{
    protected $table = 'product_change_requests';

    protected $fillable = [
        'product_id',
        'store_id',
        'action',
        'status',
        'name',
        'price',
        'status_value',
        'quantity',
        'unit',
        'product_updated_at_snapshot',
        'review_note',
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
}
