<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $config_id
 * @property String $name
 * @property String $value
 * @property String $description
 */
class Config extends Model
{
    protected $table = 'smed_config';

    public $timestamps = false;

    protected $fillable = [
        'config_id',
        'name',
        'value',
        'description',
    ];


    /**
     * @return Config
    */
    public static function getConfig($name){
        return self::query()->where('name',$name)->first()->value;
    }
}
