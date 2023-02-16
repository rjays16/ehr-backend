<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmedRepetitiveSession extends Model
{
    
    protected $table = 'smed_repetitive_session';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'order_batchid',
        'repetitive_procedure',
        'remarks',
        'session_start_date',
        'session_end_date',
        'session_start_time',
        'session_end_time',
        'history',
        'create_id',
        'create_dt',
        'modify_id',
        'modify_dt'
    ];

    public function diagnosisProcedure()
    {
        return $this->belongsTo(SmedDiagnosisProcedure::class, 'repetitive_procedure', 'id');
    }

}
