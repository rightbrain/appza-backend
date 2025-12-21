<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileVersionMapping extends Model
{
    protected $table = 'appza_mobile_version_mapping';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'mobile_app_id','app_name','mobile_version','minimum_plugin_version','latest_plugin_version','force_update','is_active','optional_message'
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
