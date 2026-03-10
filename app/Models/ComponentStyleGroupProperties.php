<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponentStyleGroupProperties extends Model
{
    use SoftDeletes;

    protected $table = 'appfiy_component_style_group_properties';

    public $timestamps = true;

    protected $fillable = ['component_id', 'name', 'input_type', 'value', 'default_value', 'style_group_id'];

    public function styleGroup(): BelongsTo
    {
        return $this->belongsTo(StyleGroup::class, 'style_group_id', 'id');
    }
}
