<?php


namespace App\API\V1\Models\laboratory;


use Illuminate\Database\Eloquent\Model;

class LaboratoryHeader extends Model
{
    protected $table = 'smed_laborder_h';

    protected $primaryKey = 'refno';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


}