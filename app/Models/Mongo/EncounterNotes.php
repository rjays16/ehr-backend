<?php

namespace App\Models\Mongo;

use App\Models\EncounterAssessment;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property object $_id
 * @property string $encounterNo
 * @property arrat $psa_name_ids
*/
class EncounterNotes extends Eloquent
{
    use SoftDeletes;

    protected $form_code = 'PA';

    protected $connection = 'mongodb';
    protected $collection = 'encounter.notes';

    protected $fillable = [
        //'_id',
        'encounterNo',
        'psa_name_ids',
        'physicalExamination',
        'physicalExaminationDetailed',
        'psa_name_ids_modified_dt',
        'psa_name_ids_modified_by',
        'physicalExaminationDetailed_modified_dt',
        'physicalExaminationDetailed_modified_by'
    ];

    protected $casts = [
        // 'fields' => 'object',
        // 'soap_diag_final' => 'object',
        // 'soap_diag_other' => 'object',
        // 'soap_chief_complaint_other' => 'object',                                    
        // 'soap_objective' => 'object',
        // 'soap_plan' => 'object',
    ];


}
