<?php

namespace App\Models\Mongo;

use App\User;
use Illuminate\Database\Query\Builder;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property String $token
 * @property String $user_id
 * @property String $uuid
 * @property String $device_unique_id
 * @property String $platform
 * @property String $model
 * @property boolean $active
*/
class UserAccessToken extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'entities.useraccesstoken';

    protected $fillable = [
        'token',
        'user_id',
        'uuid',
        'device_unique_id',
        'platform',
        'model',
    ];

    protected $casts = [
        'token' => 'string',
        'user_id' => 'string',
        'uuid' => 'string',
        'device_unique_id' => 'string',
        'platform' => 'string',
        'model' => 'string',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    /**
     * @return boolean
    */
    public static function tokenExist($token){
        return self::query()->where('token' ,$token)->exists();
    }

    /**
     * @return Builder
     * 
     * if user_id is not not empty/null then
     * token was encrypted with md5
    */
    public static function token($token, $user_id = ''){
        if($user_id){
            foreach (self::query()->where('user_id' ,(int)$user_id)->get()->toArray() as $key => $access) {
                if($access)
                    if(md5($access['token']) == $token)
                        return self::query()->find($access['_id']);
            }
            return false;
        }
        else
            return self::query()->where('token' ,$token)->first();
    }


    /**
     * @return mixed
    */
    public static function saveToken($token,$uuid,$device_unique_id, $platform, $model, $user_id)
    {
        $resp = self::_deactiveOtherTokenAccess($uuid,$device_unique_id, $platform, $model, $user_id);
//        if(!$resp)
//            return false;

        return self::query()->create([
            'token' => $token,
            'user_id' => $user_id,
            'uuid' => $uuid,
            'device_unique_id' => $device_unique_id,
            'platform' => $platform,
            'model' => $model,
        ]);
    }


    private static function _deactiveOtherTokenAccess($uuid,$device_unique_id, $platform, $model, $user_id){
        return self::query()
            ->where('user_id',$user_id)
            ->where('uuid',$uuid)
            ->where('device_unique_id',$device_unique_id)
            ->where('platform',$platform)
            ->where('model',$model)
            ->delete();
    }

    public static function removeAccess($token){
        return self::query()
            ->where('token',$token)->delete();
    }
}
