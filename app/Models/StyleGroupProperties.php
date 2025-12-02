<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StyleGroupProperties extends Model
{
    protected $table = 'appfiy_style_group_properties';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at','updated_at'];
    protected $fillable = ['style_group_id', 'style_property_id',];

    public function styleGroup()
    {
        return $this->belongsTo(StyleGroup::class, 'style_group_id', 'id');
    }

    public function styleProperty()
    {
        return $this->belongsTo(StyleProperties::class, 'style_property_id', 'id');
    }

}
