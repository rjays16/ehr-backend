<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/28/2019
 * Time: 12:20 AM
 */

namespace App\Models\Mongo\Tracer;

class TracerTriagePatientPreassessmentEncounter extends TracerEncounterAssessment
{
    protected $form_code = 'preassessment';
    protected $asses_type = 'vs';
}