<?php

namespace App\Models\Mongo;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property object $ip
 * @property string $token
 * @property arrat $active
*/
class WhitelistIp extends Eloquent
{
    use SoftDeletes;


    protected $connection = 'mongodb';
    protected $collection = 'whitelist_ip';

    protected $fillable = [
        'ip',
        'token',
        'active',
    ];

    protected $casts = [];

}
