<?php
namespace App\Services\HIS\HisRadOrder;

use App\Exceptions\EhrException\EhrException;
use App\Models\DiagnosticOrderRad;
use App\Models\Encounter;
use App\Models\HIS\HisGrantRequest;
use App\Models\HIS\HisRadiologyServe;
use App\Models\HIS\HisRadioID;
use App\Models\HIS\HisRadiologyServeDetails;
use App\Services\HIS\HisPhicCoverage\HisDiscountService;
use App\Services\Doctor\HisServices;
use Illuminate\Support\Facades\DB;

class HisRadOrderService
{
    const REF_SOURCE = "RD";
    const SOURCE_REQ = "EHR";

    const REQUEST_FLAG_CHARITY = 'charity';

    public $batch;
    /** @var Encounter $encounter */
    public $encounter;

    public function __construct($batch, $encounter)
    {
        $this->batch = $batch;
        $this->encounter = $encounter;
    }

    public static function init($batch, $encounter_no)
    {
        $encounter = Encounter::query()->find($encounter_no);
        if (is_null($encounter))
            throw new EhrException('Encounter does not exist!');
        return new HisRadOrderService($batch, $encounter);
    }

    public function save($refno)
    {

        $data = $details = [];
        $okay = false;

        $spinD = $this->encounter->hisspin0;
        $orderaddress = $spinD->getPatientAddress();

        $hisEncounter = $this->encounter->hisEncounter;
        $patient_location = $hisEncounter->getLocation();

        $doc_area = '';
        $personnel = auth()->user()->personnel;
        $dr_name = $personnel->getDoctorName();
        if(auth()->user()->personnel->assignments)
            $doc_area = auth()->user()->personnel->assignments[0]->area_id;

        $discount = (new HisDiscountService($this->encounter))->execute();

        $his_service = new HisServices($this->encounter, strftime('%Y-%m-%d %H:%M:%S'));
        $final_bill = $his_service->getFinalBill();
        $is_final = $final_bill['is_final'];

        foreach ($this->batch as $index => $orders) {
            $order = collect($orders['orders'])->recursive()->first();
            if($order->get('sname') == "RADIOLOGY"){
                if($is_final && !$order->get('cash')){
                    throw new EhrException('Charging is not allowed to this patient. This Patient was already adviced to go home.');
                }
                $hisRadServeModel = new HisRadiologyServe();
                $hisRadServeModel->refno = $refno;
                $hisRadServeModel->encounter_nr = $this->encounter->encounter_no;
                $hisRadServeModel->request_date = date('Y-m-d');
                $hisRadServeModel->request_time = date('H:i:s');
                $hisRadServeModel->pid = $this->encounter->spin;
                $hisRadServeModel->orderaddress = $orderaddress;

                $hisRadServeModel->is_cash = $order->get('cash') ? 1 : 0;
                // $hisRadServeModel->loc_code = $patient_location;
                // $hisRadServeModel->ref_source = self::REF_SOURCE;
                $hisRadServeModel->source_req = self::SOURCE_REQ;
                $hisRadServeModel->is_printed = 1;
                $hisRadServeModel->discount = 0;
                $hisRadServeModel->comments = $order->get('remarks');
                $hisRadServeModel->status = '';
                $hisRadServeModel->discountid = isset($discount['discountid']) ? $discount['discountid'] : '';
                $hisRadServeModel->create_id = auth()->user()->username;
                $hisRadServeModel->type_charge = 0;
                $hisRadServeModel->create_dt = date('Y-m-d H:i:s');
                $hisRadServeModel->ordername = $spinD->fullname();
                $hisRadServeModel->grant_type = $order->get('charge_type') ? $order->get('charge_type') : '';
                $hisRadServeModel->is_tpl = strtoupper($order->get('charge_type'))  === 'PHIC' ? 0 : 1 ;
                $hisRadServeModel->history = "Create: " . date('Y-m-d H:i:s') . " [personnel_id: " . auth()->user()->personnel_id . "]";

                if (!$hisRadServeModel->save()) {
                    throw new EhrException('Rad orders not saved.');
                }

                $this->saveHisRadioID($this->encounter->spin, auth()->user()->username);

                HisGrantRequest::query()->where('ref_no', $refno)->where('ref_source',self::REF_SOURCE)->forceDelete();
                foreach ($orders['orders'] as $key => $order) {
                    if ($order['kardexGroup'] == 'Diagnostic') {
                        $orderD = DiagnosticOrderRad::query()->where('id', $order['id'])->first();
                        if($orderD){
                            $hisRadServeDetialsModel = new HisRadiologyServeDetails();
                            $hisRadServeDetialsModel->refno = $refno;
                            $hisRadServeDetialsModel->service_code = $orderD->service_id;
                            $hisRadServeDetialsModel->request_doctor = $orderD->doctor_id;
                            $hisRadServeDetialsModel->manual_doctor = $dr_name;
                            $hisRadServeDetialsModel->is_in_house = 1;
                            $hisRadServeDetialsModel->headpasswd = '';
                            $hisRadServeDetialsModel->headID = '';
                            $hisRadServeDetialsModel->remarks = '';
                            $hisRadServeDetialsModel->approved_by_head = '';
                            $hisRadServeDetialsModel->request_flag = strtolower($orderD->transaction_type) == 'phic' ? 'phic' : null;
                            $hisRadServeDetialsModel->price_cash = $orderD->price_cash;
                            $hisRadServeDetialsModel->price_cash_orig = $orderD->price_cash_orig;
                            $hisRadServeDetialsModel->price_charge = $orderD->price_charge;
                            $hisRadServeDetialsModel->clinical_info = $orderD->impression;
                            $hisRadServeDetialsModel->status = DiagnosticOrderRad::STATUS_PENDING;
                            $hisRadServeDetialsModel->create_id = auth()->user()->username;
                            $hisRadServeDetialsModel->create_dt = date('Y-m-d H:i:s');
                            $hisRadServeDetialsModel->history = "Create request from Diagnostic EHR[Mobile] " . date('Y-m-d H:i:s') . " " . auth()->user()->username;

                            if (!$hisRadServeDetialsModel->save()) {
                                throw new EhrException('Rad orders details not saved.');
                            }


                            if($orderD->price_cash == 0){
                                $grantReq = new HisGrantRequest();
                                $grantReq->ref_no = $refno;
                                $grantReq->ref_source = self::REF_SOURCE;
                                $grantReq->service_code = $orderD->service_id;
                                $grantReq->save();

                                HisRadiologyServeDetails::query()->where('refno', $refno)->where('service_code',$orderD->service_id)
                                ->update([
                                    'request_flag'  => self::REQUEST_FLAG_CHARITY,
                                    'history'       => DB::raw("CONCAT(history,  'Create request details [".self::REQUEST_FLAG_CHARITY."] at radiology-social service  " . date('Y-m-d H:i:s') . " " . auth()->user()->username . "\n' )"),
                                    'modify_id'     => auth()->user()->username,
                                    'modify_dt'     => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }
                DiagnosticOrderRad::query()
                    ->where('order_batchid', '=', $orders['id'])
                    ->update(['refno' => $refno]);


                $hisRadServe['details'] = $details;
                $data[] = $hisRadServe;
            }
        }


        return [
            'msg' => 'HIS Radiology Order saved',
            'hisbatch' => $data,
        ];
    }


    public static function getNewRadRefno($refno)
    {
        $model= new HisRadiologyServe();
        $newRefno = $model->getNewRefno($refno);

        return $newRefno;
    }

    public function saveHisRadioID($pid , $personnel_nr){
       
        $rid = $this->getRadioTracker();
       
        $model = new HisRadioID();
        
        $model->rid = $rid;
        $model->pid = $pid;
        $model->create_id = $personnel_nr;
        $model->create_dt = date('Y-m-d H:i:s');

        if (!$model->save()) {
            throw new \Exception('Unable to save Radiology ID' );
         }
    }

    public function getRadioTracker()
    {
           $result =   DB::connection('his_mysql')->table('seg_radio_id')->max('rid');
	  return $result ? $result +1 : false;
    }
}
