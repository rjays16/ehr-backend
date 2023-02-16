<?php


namespace App\Models;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

/**
 * @property $encounter_no
 * @property $focus
 * @property $data
 * @property $action
 * @property $response
 * @property $create_dt
 * @property $modify_dt
 */

class TracerNurseNotes extends Eloquent
{
    use SoftDeletes;
    use HybridRelations;

    protected $form_code = 'NN';

    protected $connection = 'mongodb';
    protected $collection = 'entities.nurse_notes';

    protected $fillable = [
        'encounter_no',
        'focus',
        'data',
        'action',
        'response',
        'create_dt',
        'modify_dt',
    ];

}