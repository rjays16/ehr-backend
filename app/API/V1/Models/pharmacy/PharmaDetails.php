<?php


namespace App\API\V1\Models\pharmacy;


use Illuminate\Database\Eloquent\Model;

class PharmaDetails extends Model
{
    protected $table = 'smed_pharmaorder_d';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

}