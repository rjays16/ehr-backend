<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmedDiagnosisProcedure extends Model
{
    protected $table = 'smed_diagnosis_procedure';

    public $timestamps = false;

    protected $fillable = [
        'procedure',
        'rvs_code',
        'create_id',
        'create_dt',
        'modify_id',
        'modify_dt',
    ];
}
