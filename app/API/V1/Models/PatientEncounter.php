<?php


namespace App\API\V1\Models;


use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{

    public $table = 'smed_encounter';

    public $fillable = [
        'encounter_no',
        'encounter_date',
        'spin',
        'is_infectious',
        'discharge_dt',
        'is_discharged',
        'mgh_dt',
        'is_mgh',
        'death_dt',
        'history',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
        'disposition_id',
        'is_pregnant',
        'hci_code',
        'refer_todoctor',
        'is_cancel',
        'cancel_reason',
        'cancel_dt',
        'cancel_id',
        'mgh_id',
        'discharge_id',
        'is_package',
        'admit_diagnosis2',
        'parent_encounter_nr'
    ];
}