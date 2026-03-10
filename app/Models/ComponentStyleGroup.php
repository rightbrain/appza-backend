<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentStyleGroup extends Model
{
    protected $table = 'appfiy_component_style_group';

    public $timestamps = true;

    protected $fillable = ['component_id', 'style_group_id', 'is_checked', 'style_group_label'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id');
    }

    public function styleGroup(): BelongsTo
    {
        return $this->belongsTo(StyleGroup::class, 'style_group_id', 'id');
    }
}
