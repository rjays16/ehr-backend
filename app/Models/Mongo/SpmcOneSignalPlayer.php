<?php

namespace App\Models\Mongo;

use Illuminate\Database\Query\Builder;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

/**
*    @property string $player_id
*    @property string $user_id
*    @property boolean $is_telemed
*    @property string $onesignal_app_name
 */
class SpmcOneSignalPlayer extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'spmc_onesignal_players';

    protected $fillable = [
        'player_id',
        'user_id',
        'is_telemed',
        'onesignal_app_name'
    ];

}
