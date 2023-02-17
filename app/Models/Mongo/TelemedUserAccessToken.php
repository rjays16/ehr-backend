<?php

namespace App\Models\Mongo;

use App\User;
use Illuminate\Database\Query\Builder;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property String $consult_meeting_uid
 * @property String $access_token
 * @property boolean $is_expired
 * @property String $user_id
*/
class TelemedUserAccessToken extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'telemed_access';


    public static function token($token){
        return self::query()->where('access_token' ,$token)->where('is_expired',false)->first();
    }
}
