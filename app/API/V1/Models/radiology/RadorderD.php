<?php


namespace App\API\V1\Models\radiology;


use Illuminate\Database\Eloquent\Model;

class RadorderD extends Model
{
    protected $table = 'smed_radorder_d';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}