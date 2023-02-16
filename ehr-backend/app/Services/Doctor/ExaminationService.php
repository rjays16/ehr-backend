<?php


namespace App\Services\Doctor;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\Mongo\EncounterNotes;
use App\Models\Mongo\Tracer\TracerActivity;
use App\Models\PatientCatalog;
use App\Models\PersonCatalog;
use App\Models\PhysicalExaminationCatalog;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\FormActionHelper;
use Illuminate\Support\Facades\DB;

class ExaminationService extends PertinentSignAndSymptomsService
{


    /**
     * @var array|null
     */
    protected $data;


    public function getPhysicalExamCategoriesArray(){
        $data = [];
        foreach ($this->getPhysicalExamCategories() as $en){
            $data[] = [
                'label' => $en->category
            ];
        }

        return $data;
    }

    public static function init($enc)
    {
        $encounter = Encounter::query()->find($enc);
        if(!$encounter)
            throw new EhrException('Encounter does not exist.', 404);

        return new ExaminationService($encounter);
    }


    public function getMPhysicalExamOptions(){
        $data = [];
        foreach ($this->getPhysicalExamCategories() as $en){
            $options = $this->getEncounterFindingByCategory($en->category);
            $data[$en->category][] = $options['options2'];
        }

        return $data;
    }


    public function getMPhysicalExamData(){
        $data = [];
        foreach ($this->getPhysicalExamCategories() as $en){
            $options = $this->getEncounterFindingByCategory($en->category);
            $data[$en->category] = $options['myfindings'];
        }

        $peData = $this->getData(); // PE mongo data
        $modifier = FormActionHelper::getModifier('',[
            "modified_dt" => $peData['physicalExaminationDetailed_modified_dt'],
            "modified_by" => $peData['physicalExaminationDetailed_modified_by'],
        ]);
        return [
            'examinationData'=>collect($data)->recursive()->map(function($item){
                return $item->map(function($itemm){
                    return $itemm == "" ? null: $itemm;
                });
            })->toArray(),
        ]+$modifier;
    }

    /**
     * @var $data_list
     * [
     *      category1 : {
     *          id1: '',
     *          id2: '',
     *          others: 'remarks here'
     *      }
     *      category2 : {
     *          id1: '',
     *          id2: '',
     *          others: 'remarks here'
     *      }
     * ]
     * */

    public function savePhysicalExaminationDetailedList($data_list)
    {
        if(!$this->permService->hasPhysExamEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        foreach ($this->getPhysicalExamCategoriesArray() as $category_key => $category) {

            $category = $category['label'];

            if(!isset($data_list[$category])){
                throw new EhrException("Examination finding for '{$category}' is required.", 404);
            }

            foreach ($data_list[$category] as $key => $remarks) {
                // validate data
                if($key != 'others'){
                    $found = PhysicalExaminationCatalog::query()
                        ->where('id', $key)
                        ->where('category', $category)
                        ->first();
                    if(!$found){
                        throw new EhrException("Examination finding for '{$category}' does not exist.", 404);
                    }
                }
                else if((strtolower($category) == 'general survey' && $key == 2 && trim($remarks) == '')){
                    throw new EhrException("Remarks is required for 'Altered Sensorium'.", 500);
                }
                else if(($key == 'others' || $key == 2) && trim($remarks) == ''){
                    throw new EhrException("Remarks '{$category}' must not be empty.", 500);
                }
                else if(($key == 'others' || $key == 2) && strlen(trim($remarks)) > 2000){
                    throw new EhrException("Remarks '{$category}' must not be greater than 2000 characters.", 500);
                }
            }
        }

        $resp =  $this->savePhysicalExaminationDetailed($data_list);
        return [
            'msg' => 'Findings saved!'
        ]+$resp;
    }


    /**
     * @return bool
     */
    public function savePhysicalExaminationDetailed($data)
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
        
        $modifier = FormActionHelper::getFormTimeStamp('physicalExaminationDetailed_');
        $result =  $this->encounternote->update([
            'physicalExaminationDetailed' => $data,
        ]+$modifier);

        if(!$result)
            throw new EhrException('Failed to save physical examination.');
        
        return FormActionHelper::getModifier('',[
            "modified_dt" => $modifier['physicalExaminationDetailed_modified_dt'],
            "modified_by" => $modifier['physicalExaminationDetailed_modified_by'],
        ]);
        
    }


    public function getPhysicalExamCategories(){
        return PhysicalExaminationCatalog::query()
                ->groupBy('category')
                ->orderBy('category_sequence')
                ->get();
    }

    public function getPhysicalExaminationDetailed()
    {
        return $this->getData()['physicalExaminationDetailed'];
    }


    public function getEncounterFindingByCategory($category, $include_Alloptions = true){
        foreach (PhysicalExaminationCatalog::query()
                    ->where('category', $category)
                    ->where('phic_id_active', 1)
                    ->orderBy('findings_sequence')
                    ->groupBy('phys_name')
                    ->get() as $key => $entries){
            if($include_Alloptions)
                $data['options'][] = $entries->phys_name;

            if($entries->is_default)
                $data['options_default'][] = $entries->phys_name;

            $data['options2'][$key]['id'] = $entries->id;
            $data['options2'][$key]['text'] = $entries->phys_name;
        }

        if(strtolower($category) !== 'general survey'){
            $data['options2'][$key+1]['id'] = 'others';
            $data['options2'][$key+1]['text'] = 'Others';
        }
        if(isset($this->encounter->encounter_no)){
            $data['myfindings'] = $this->getMyFindingOptions($category);
            // $data['myfindings_remark'] = $this->getMyFindingRemark($category);
        }

        if(!$include_Alloptions)
            unset($data['options']);
        return $data;
    }

    public function getMyFindingOptions($category){
        $findins = $this->getData()['physicalExaminationDetailed'];
        if($findins[$category]){

            return $findins[$category];
        }
        else{
            return [];
        }
    }

    public function getMyFindingRemark($category){
        $findins = $this->getData()['physicalExaminationDetailed'];
        if($findins[$category]){
            return $findins[$category]['remark'];
        }
        else{
            return "";
        }
    }


    public static function config()
    {
        return [
            'm-patient-examination' => [
                'examination-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'examination-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'examination' => (new ExaminationService(new Encounter()))->getMPhysicalExamOptions()
                ]
            ]
        ];
    }
}