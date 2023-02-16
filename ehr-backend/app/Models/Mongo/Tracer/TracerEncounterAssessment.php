<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/27/2019
 * Time: 12:22 AM
 */

namespace App\Models\Mongo\Tracer;

use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\EncounterAssessment;
use App\Models\Mongo\PatientPreAssessment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TracerEncounterAssessment
{
    protected $form_code = 'PA';
    protected $asses_type = 'PA';
    
    /**
     * @var array
     * Temporary saved list of 
     * PatientPreAssessment data before saved.
     */
    private $tracesActivity = [];

    /**
     * @var EncounterAssessment
    */
    private $model;

    /**
     * @var PatientPreAssessment
    */
    private $tracer;

    function __construct(EncounterAssessment $model)
    {   
        $this->model = $model;
        $this->initTracerModel();
    }

    public static function init(){
        return new TracerEncounterAssessment(new EncounterAssessment());
    }


    /**
     * @return PatientPreAssessment
    */
    public function getTracerModel()
    {
        return $this->tracer;
    }

    public function initTracerModel()
    {
        if(isset($this->model->document_id)){
            $this->tracer = PatientPreAssessment::query()->find($this->model->document_id);
        }
    }

    public function getFieldName($fieldname){
        if($this->tracer)
            return $this->tracer->fields[$fieldname]['value'];
        else   
            return '';
    }



    /**
     * @return EncounterAssessment
    */
    public function getAssessment()
    {
        return $this->model;
    }

    public function ressetTracerUpdate()
    {   
        /**
         * @var TracerActivity $tracer
         * @var Collection $tracer->tracer
         * @var string $tracer->type
        */
        foreach ($this->tracesActivity as $key => $tracer) {

            $id = $tracer->tracer->get('_id');

            $ass = PatientPreAssessment::query()->find($id);
            if($ass)
                if($tracer->type == 'new')
                    $ass->forceDelete();
                else{
                    $tracer->tracer = $tracer->tracer->forget('_id');
                    $ass->update($tracer->tracer->toArray());
                }

        }
    }

    /**
     * @var Encounter $encounter
     * @var mixed $data
     * @var bool $newEntry
    */
    public function saveAssessment(Encounter $encounter, $data = [], $newEntry = false)
    {
        $document_id = null;
        $trace_type = 'old';

        if(isset($this->model->document_id)){
            $this->tracer = PatientPreAssessment::query()->find($this->model->document_id);
        }

        if ($newEntry || is_null($this->tracer)) {
            $tracerAssesssment = PatientPreAssessment::query()->create($data);
            if(!$tracerAssesssment)
                throw new EhrException('Failed to save assessment document.');
            $trace_type = 'new';
            $this->tracer = PatientPreAssessment::query()->find($tracerAssesssment->_id);
        }
        

        /**
         * record PatientPreAssessment traces activity
         */
        $this->tracesActivity[] = new TracerActivity($trace_type, $this->tracer);

        $this->tracer->update($data);

        // create new Encounter assessment every time save and update to DB
        $this->model = new EncounterAssessment();
        $this->model->id = (string) Str::uuid();

        $this->model->encounter_no = $encounter->encounter_no;
        if($this->asses_type != 'vs')
            $this->model->deptenc_no = $encounter->deptEncounter->deptenc_no;

        $this->model->assessment_date = date("Y-m-d H:i:s", time());
        $this->model->document_id = $this->tracer->_id;
        $this->model->assess_type = $this->asses_type;
        $this->model->model_id = 24;
        $this->model->form_code = $this->form_code;
        $this->model->create_id = auth()->user()->personnel->personnel_id;
        $this->model->modify_id = auth()->user()->personnel->personnel_id;
        $this->model->create_dt = date('Y-m-d H:i:s');
        $this->model->modify_dt = date('Y-m-d H:i:s');
        
        if(!$this->model->save()){
            $this->tracer->forceDelete();
            throw new EhrException('Failed to save assessment.');
        }
        return true;



    }
}
