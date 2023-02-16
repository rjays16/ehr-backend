<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
* @property $drug_code
* @property $description
* @property $gen_code
* @property $salt_code
* @property $form_code
* @property $strength_code
* @property $unit_code
* @property $package_code
 */
class PhilMedicine extends Model
{
    public $table = 'smed_phil_medicine';
    protected $primaryKey = 'drug_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    // public $fillable = [
    //     'drug_code',
    //     'description',
    //     'gen_code',
    //     'salt_code',
    //     'form_code',
    //     'strength_code',
    //     'unit_code',
    //     'package_code',
    // ];
}
