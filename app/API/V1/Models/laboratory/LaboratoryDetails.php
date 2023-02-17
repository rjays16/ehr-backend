<?php


namespace App\API\V1\Models\laboratory;


use Illuminate\Database\Eloquent\Model;

class LaboratoryDetails extends Model
{
    protected $table = 'smed_laborder_d';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}