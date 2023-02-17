<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisGrantRequest extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_granted_request';

    protected $primaryKey = 'grant_no';
    public $incrementing = false;
    public $timestamps = false;

    

}
