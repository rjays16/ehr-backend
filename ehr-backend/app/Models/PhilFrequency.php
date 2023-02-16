<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilFrequency extends Model
{
    protected $table = 'smed_phil_frequency';

    protected $primaryKey = 'frequency_code';
    protected $keyType = 'string';
    public $timestamps = false;

    public $fillable = [
        'frequency_code',
        'frequency_disc',
        'is_diagnostic',
        'is_med',
    ];
}
