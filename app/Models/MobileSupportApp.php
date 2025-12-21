<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSupportApp extends Model
{
    protected $table = 'appza_support_mobile_apps';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at','updated_at'];
    protected $fillable = [
        'name',
        'slug',
        'prefix',
        'image',
        'title',
        'description',
        'others',
        'is_disable',
        'sort_order',
        'status'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

}
