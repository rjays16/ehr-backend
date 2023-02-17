<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexMedication extends Model
{
    protected $table = 'smed_kardex_medication';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function kardexBatch()
    {
        return $this->belongsTo(MedsOrder::class, 'docorder_id', 'id');
    }

    public function nurse()
    {
        return $this->belongsTo(PersonnelCatalog::class, 'nurse_id','personnel_id');
    }
}