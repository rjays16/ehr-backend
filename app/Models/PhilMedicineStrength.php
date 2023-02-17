<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilMedicineStrength extends Model
{
    public $table = 'smed_phil_medicine_strength';
    protected $primaryKey = 'strength_code';
    protected $keyType = 'string';
    public $timestamps = false;

    public $fillable = [
        'strength_code',
        'strength_disc',
    ];
}
