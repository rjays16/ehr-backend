<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 9:05 PM
 */

namespace App\Services\Diagnostic;


use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use Illuminate\Support\Str;
use App\Models\EncounterDxpr;
use App\Models\Icd10Code;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\Soap\SoapService;
use App\Services\FormActionHelper;

class DiagnosticsService
{


    /**
     * @return array
    */
    public function searchIcds($q){
        $result = Icd10Code::query()
                    ->select('icd_code as id', 'icd_desc as text')
                    ->where('is_deleted',0)
                    ->whereRaw("is_deleted is not null")
                    ->where('is_phic',1)
                    ->whereRaw("concat(LCASE(icd_code), ' ' , LCASE(icd_desc)) like '%{$q}%'")
                    ->limit(10)
                    ->get();
        return collect($result)->toArray();
        
    }


    /**
     * @return Collection
     * 
     */
    public function saveDiagnosis($data, $sendToHis = true)
    {
        
            
        $data = collect($data);
        if(!($data->has('id') && $data->has('icd_code')))
                throw new EhrException('Submitted data is incomplete.');

        $soap = SoapService::init($data->get('id'));
        
        if(!$soap->permService->hasSoapEdit())
            throw new EhrException(PermissionService::$errorMessage);

        $model = new EncounterDxpr();

        if ($model->checkExisting($data->toArray())){
            throw new EhrException('ICD code already exist.', 500);
        }

        $icd = Icd10Code::query()->find($data->get('icd_code'));
        if(!$icd){
            throw new EhrException('ICD not exist.', 500);
        }

        $model->id = (string) Str::uuid();
        $model->encounter_no = $data->get('id');
        $model->icd_code = $icd->icd_code;
        $model->alt_diagnosis = $icd->icd_desc;
        $model->create_dt = date('Y-m-d H:i:s');
        $model->create_id = auth()->user()->personnel_id;
        $model->modify_dt = $model->create_dt;
        $model->modify_id = $model->create_id;

        if ($model->save()){

            if($sendToHis){
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOADiagnosisICD($data->get('id'), $soap->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            }else $resp['status'] = true;

            if($resp['status']){
                return collect([
                    'msg' => 'Successfully Saved' ,
                    'clinicalimp_new' => isset($soap) ? $soap->getClinicalImpression($data->get('id')) : '',
                    'saved' => true,
                    'encoded' => self::getEncoder($data->get('id'))
                ]);
            }
            else
                throw new EhrException($resp['msg'], 500,['his_data' => $his->getResponseData()], true);

        }
        else
            throw new EhrException('Failed to save ICD10.', 500, true);
    }
    

    public static function getEncoder($encounterNo)
    {
        $model = EncounterDxpr::query()->where('encounter_no', $encounterNo)->orderByDesc('create_dt')->first();
        if($model['modify_id']) {
            $date = $model['modify_dt'] ? $model['modify_dt'] : '';
            $id = $model['modify_id'];
        } else {
            $date = $model['create_dt'] ? $model['create_dt'] : $model['modify_dt'];
            $id = $model['create_id'];
        }

        $modifier = FormActionHelper::getModifier('', [
            "modified_dt" => $date,
            "modified_by" => $id,
        ]);

        return [
                'name_en' => $modifier['modified_by'],
                'date_en' => $modifier['modified_dt'],
        ];

    }


    /**
     * @return Collection
     * 
     */
    public function deleteDiagnosis($id, $sendToHis = true)
    {
        
        $model = EncounterDxpr::query()->find($id);
        if(is_null($model))
            throw new EhrException('ICD is not added yet.',500);
            
        $model->is_deleted = 1;
        $model->modify_dt = date('Y-m-d H:i:s');
        $model->modify_id = auth()->user()->personnel_id;
        $resp = collect();
        if ($model->save()){

            $soap = SoapService::init($model->encounter_no);
            if(!$soap->permService->hasSoapEdit()) // rollback if not allowed or discharged
                throw new EhrException(PermissionService::$errorMessage);

            if($sendToHis){
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = collect($his->onSaveSOADiagnosisICD($model->encounter_no, $soap->getSoap()));
                //  ===================== HIS trigger event to update there SOAP ================ false
            }else $resp->put('status',true);

            if($resp->get('status')){
                return collect([
                    'msg' => 'Successfully deleted!' ,
                    'clinicalimp_new' => isset($soap) ?  $soap->getClinicalImpression($model->encounter_no) : '',
                    'saved' => true,
                    'className' => 'success',
                    'encoded' => self::getEncoder($model->encounter_no)
                ]);
            }
            else
                throw new EhrException($resp->get('msg'),500,['data' =>$his->getResponseData()], true);

        }
        else
            throw new EhrException('Failed to deleted diagnosis!',500,['className' => 'error']);
    }

}