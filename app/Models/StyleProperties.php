<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StyleProperties extends Model
{
    use SoftDeletes;

    protected $table = 'appfiy_style_properties';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['deleted_at','created_at','updated_at'];
    protected $fillable = ['name', 'input_type', 'value', 'default_value'];

    public function styleGroupProperties()
    {
        return $this->hasMany(StyleGroupProperties::class, 'style_property_id', 'id');
    }

}
