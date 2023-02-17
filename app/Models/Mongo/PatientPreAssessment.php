<?php

namespace App\Models\Mongo;

use App\Models\EncounterAssessment;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property object $_id
 * @property Object $soap_diag_final
 * @property Object $soap_diag_other
 * @property Object $fields
*/
class PatientPreAssessment extends Eloquent
{
    use SoftDeletes;

    protected $form_code = 'PA';

    protected $connection = 'mongodb';
    protected $collection = 'entities.patientpreassessment';

    protected $fillable = [
        //'_id',
        'fields',
        'soap_diag_final',
        'soap_diag_other',
        'soap_chief_complaint_other',
        "soap_assessment_clinical_imp_defined",
        'soap_objective',
        'soap_plan',
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
