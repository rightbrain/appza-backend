<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentImportLog extends Model
{
    protected $table = 'appza_component_import_logs';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = [
        'component_name',
        'success',
        'message',
        'source'
    ];

    public static function boot() {
        parent::boot();
        self::creating(function ($model) {
            $date =  new \DateTime("now");
            $model->created_at = $date;
        });

        self::updating(function ($model) {
            $date =  new \DateTime("now");
            $model->updated_at = $date;
        });
    }

}
