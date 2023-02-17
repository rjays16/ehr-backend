<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 9:05 PM
 */

namespace App\Services\Doctor;


use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\Encounter;
use App\Models\Mongo\EncounterNotes;
use App\Models\Mongo\Tracer\TracerActivity;
use App\Models\PatientCatalog;
use App\Models\PresentIllness;
use App\Models\PastMedicalHistory;
use App\Models\PertinentSignSymptomsOnDiagnosisCatalog;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\FormActionHelper;
use Illuminate\Database\Query\Builder;

class PertinentSignAndSymptomsService
{
    /**
     * @var Encounter
     */
    public $encounter;

    /**
     * @var EncounterNotes $encounternote
     */
    private $encounternote;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array
     * Temporary saved list of 
     * PatientPreAssessment data before saved.
     */
    private $tracesActivity = [];


    /** @var PermissionService $permService */
    public $permService;

    function __construct($encounter = null)
    {
        $this->encounter = $encounter;
        $this->permService = new PermissionService($encounter);
    }


    public static function init($enc)
    {
        $encounter = Encounter::query()->find($enc);
        if(!$encounter)
            throw new EhrException('Encounter does not exist.', 404);

        return new PertinentSignAndSymptomsService($encounter);
    }

    public function getCatalog(){
        $model = new PertinentSignSymptomsOnDiagnosisCatalog();
        return $model->getPertinentSASonAdmisDefaultDiags();
    }


    public function getMCatalog(){
        $model = new PertinentSignSymptomsOnDiagnosisCatalog();
        return $model->getPertinentSASonAdmisDefaultCatalog();
    }


    /**
     * 
     * @var string $q
     */
    public function getSearchedOthersCatalog($q){
        $model = PertinentSignSymptomsOnDiagnosisCatalog::query()
                    ->select('id','psa_name as text')        
                    ->where('is_others',1)
                    ->where('psa_name','like',"%{$q}%")
                    ->limit(50)
                    ->get();
        
        return $model->toArray();
    }

    

    public function getSelectedData()
    {
        $catalogM = new PertinentSignSymptomsOnDiagnosisCatalog();

        return $catalogM->getPertinentSASonAdmisSelected($this->clearNull($this->getData()['psa_name_ids']));
    }

    public function getMSelectedData()
    {
        $catalogM = new PertinentSignSymptomsOnDiagnosisCatalog();
        $data = collect($this->getSelectedData());
        return [
            'selected' => $data->get('data'),
            'pains' => $data->get('opt_2'),
            'others' => $data->get('opt_3'),
        ]+FormActionHelper::getModifier('',[
            'modified_dt' => $this->getData()['psa_name_ids_modified_dt'],
            'modified_by' => $this->getData()['psa_name_ids_modified_by'],
        ]);
    }

    /**
     * @return EncounterNotes
     */
    public function getData($include_id = true)
    {
        if($this->data == null)
            $this->data = EncounterNotes::query()->where('encounterNo', $this->encounter->encounter_no)->first();
        return $this->data;
    }


    public function clearNull($data){
        $ids = [];
        if(is_null($data))
            $data = [];

        foreach ($data as $key => $value) {
            if($value)
                $ids[] = $value;
        }
        return $ids;
    }
    


    /**
     * 
     * @var string $q
     */
    public function getSearchedPainsCatalog($q){
        $model = PertinentSignSymptomsOnDiagnosisCatalog::query()
                    ->select('id','psa_name as text')
                    ->where('is_pain',1)
                    ->where('psa_name','like',"%{$q}%")
                    ->limit(50)
                    ->get();
        
        return $model->toArray();
    }


    /**
     * @param Array $data  // list of 'psa_name' id
     * ->    [
     *       'vpsa_names' => [
     *           'psa_name' => 1,
     *           'psa_name' => 2,
     *       ],
     *       'pains' => ['psa_name','psa_name'], // array of names
     *       'others' => ['psa_name','psa_name'], // array of names
     *   ]
     *
     * @return array
     */
    public function savePertinents($data)
    {
        if(!$this->permService->hasPSignsEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $final_ids = [];

        $catalogM = new PertinentSignSymptomsOnDiagnosisCatalog();
        
        foreach ($data['psa_names'] as $d){

            if(!($d['psa_name'] == 27 || $d['psa_name'] == 36)){
                if($catalogM->isIdeExist($d['psa_name']))
                    $final_ids[] = $d['psa_name'];
            }
            else if($d['psa_name'] == 27){ // if pains is selected
                foreach ($data['pains'] as $pains_name){
                    $catg = $catalogM->isNameExist($pains_name);
                    if(!$catg){
                        $catg = $catalogM->newCatalog($pains_name, 1, 0);
                        if(!$catg)
                            continue;
                        else
                            $final_ids[] = (string) $catg;
                    }
                    else{
                        $final_ids[] = (string) $catg->id;
                    }
                }
            }
            else if($d['psa_name'] == 36){ // if others is selected
                foreach ($data['others'] as $pains_name){
                    $catg = $catalogM->isNameExist($pains_name);
                    if(!$catg){
                        $catg = $catalogM->newCatalog($pains_name, 0, 1);
                        if(!$catg)
                            continue;
                        else
                            $final_ids[] = (string) $catg;
                    }
                    else{
                        $final_ids[] = (string) $catg->id;
                    }
                }
            }
        }

        
        return $this->savePertinentIDs($final_ids);
        
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

            $ass = EncounterNotes::query()->find($id);

            if($tracer->type == 'new')
                $ass->forceDelete();
            else{
                $tracer->tracer = $tracer->tracer->forget('_id');
                $ass->update($tracer->tracer->toArray());
            }

        }
    }

    /**
     * @return bool
     */
    public function savePertinentIDs($final_ids)
    {
        $this->encounternote = EncounterNotes::query()->where('encounterNo',$this->encounter->encounter_no)->first();
        if(is_null($this->encounternote)){
            $this->encounternote = EncounterNotes::query()->create([
                'encounterNo' => $this->encounter->encounter_no,
            ]);
            $this->tracesActivity[] = new TracerActivity('new', $this->encounternote);
        }
        else
            $this->tracesActivity[] = new TracerActivity('old', $this->encounternote);
        
        $modifier = FormActionHelper::getFormTimeStamp('psa_name_ids_');
        $this->encounternote->update(collect([
            'psa_name_ids' => $final_ids,
        ])->merge($modifier)
        ->merge([
            'psa_name_ids_modified_dt' => date('m-d-Y h:i a', strtotime($modifier['psa_name_ids_modified_dt']))
        ])
        ->toArray());

        return [
            'msg' => 'Successfully Saved!',
        ]+FormActionHelper::getModifier('',[
            'modified_dt' => $modifier['psa_name_ids_modified_dt'],
            'modified_by' => $modifier['psa_name_ids_modified_by'],
        ]);
        
    }



    public static function getOptions():array
    {
        return (new PertinentSignAndSymptomsService(new Encounter()))->getMCatalog();
    }

    
    public static function config()
    {
        return [
            'm-patient-psigns' => [
                'psigns-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'psigns-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    // 'psigns' => (new PertinentSignAndSymptomsService(new Encounter()))->getMCatalog()
                    'psigns' => []
                ]
            ]
        ];
    }
}