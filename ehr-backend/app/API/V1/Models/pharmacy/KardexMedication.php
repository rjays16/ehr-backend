<?php


namespace App\API\V1\Models\pharmacy;


use Illuminate\Database\Eloquent\Model;

class KardexMedication extends Model
{

    protected $table = 'smed_kardex_medication';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}