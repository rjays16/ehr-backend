<?php

namespace App\Models\HIS;


use Illuminate\Database\Eloquent\Model;

class HisUsers extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_users';

    protected $primaryKey = 'login_id';
    protected $keyType = 'string';
   
}
