<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    public $table = 'smed_phil_routes';

    protected $primaryKey = 'route_code';
    protected $keyType = 'string';
    public $timestamps = false;

    public $fillable = [
        'route_code',
        'route_disc',
    ];
}
