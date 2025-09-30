<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'image'];
    protected $appends = ['image_url'];
    protected $hidden = ['image'];

    public function stores()
    {
        return $this->belongsToMany(User::class, 'store_category', 'category_id', 'store_id');
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

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
