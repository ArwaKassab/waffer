<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'id','name', 'email', 'password', 'type' ,'phone' ,'area_id'
    ];

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
}

