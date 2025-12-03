<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentStyleGroupProperties extends Model
{

    protected $table = 'appfiy_component_style_group_properties';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['deleted_at','created_at','updated_at'];
    protected $fillable = ['component_id', 'name', 'input_type', 'value','style_group_id'];

    public function styleGroup()
    {
        return $this->belongsTo(StyleGroup::class, 'style_group_id', 'id');
    }
}
