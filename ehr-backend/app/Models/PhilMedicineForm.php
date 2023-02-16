<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilMedicineForm extends Model
{
    public $table = 'smed_phil_medicine_form';

    protected $primaryKey = 'form_code';
    public $timestamps = false;

    public $fillable = [
        'form_code',
        'form_desc',
    ];
}
