<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/27/2019
 * Time: 12:22 AM
 */

namespace App\Models\Mongo\Tracer;



class TracerPatientPreassessmentEncounter extends TracerEncounterAssessment
{
    protected $form_code = 'PA';
    protected $asses_type = 'PA';
        
}