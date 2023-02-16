<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexReferral extends Model
{
    protected $table = 'smed_kardex_referral';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
