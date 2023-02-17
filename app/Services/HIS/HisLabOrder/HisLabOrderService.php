<?php
/**
 * File HisLabOrderService.php
 * @author Jan Chris Ogel <iamjc93@gmail.com>
 * @copyright 2020, Segworks Technologies Inc.
 * Date: 9/30/2020
 * Time: 12:31 PM
 */

namespace App\Services\HIS\HisLabOrder;

use App\Exceptions\EhrException\EhrException;
use App\Models\DiagnosticOrderLab;
use App\Models\Encounter;
use App\Models\HIS\HisGrantRequest;
use App\Models\HIS\HisLaboratoryServe;
use App\Models\HIS\HisLaboratoryServeDetails;
use App\Models\HIS\HisLabTracker;
use App\Models\PersonCatalog;
use App\Models\PersonnelCatalog;
use App\Services\Diagnostic\UpdateRefnoLabOrder;
use App\Services\HIS\HisLabTracker\GetRefnoTracker;
use App\Services\HIS\HisLabTracker\UpdateRefnoTracker;
use App\Services\HIS\HisPhicCoverage\HisDiscountService;
use Illuminate\Support\Facades\DB;

class HisLabOrderService
{
    const REF_SOURCE = "LB";
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
        return new HisLabOrderService($batch, $encounter);
    }

    public function save($refno)
    {
//        $notify = NotificationActiveResource::instance();

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

        foreach ($this->batch as $index => $orders) {
            $order = collect($orders['orders'])->recursive()->first();
            if($order->get('sname') == "LABORATORY"){
                $hisLabServeModel = new HisLaboratoryServe();
                $hisLabServeModel->refno = $refno;
                $hisLabServeModel->encounter_nr = $this->encounter->encounter_no;
                $hisLabServeModel->serv_dt = date('Y-m-d');
                $hisLabServeModel->serv_tm = date('H:i:s');
                $hisLabServeModel->pid = $this->encounter->spin;
                $hisLabServeModel->orderaddress = $orderaddress;

                $hisLabServeModel->is_cash = $order->get('cash') ? 1 : 0;
                $hisLabServeModel->loc_code = $patient_location;
                $hisLabServeModel->ref_source = self::REF_SOURCE;
                $hisLabServeModel->source_req = self::SOURCE_REQ;
                $hisLabServeModel->is_printed = 0;
                $hisLabServeModel->discount = 0;
                $hisLabServeModel->comments = $order->get('remarks');
                $hisLabServeModel->walkin_id_number = '';
                $hisLabServeModel->custom_ptype = '';
                $hisLabServeModel->headpasswd = '';
                $hisLabServeModel->headID = '';
                $hisLabServeModel->remarks = '';
                $hisLabServeModel->status = '';
                $hisLabServeModel->discountid = isset($discount['discountid']) ? $discount['discountid'] : '';
                $hisLabServeModel->approved_by_head = '';
                $hisLabServeModel->parent_refno = '';
                $hisLabServeModel->create_id = auth()->user()->username;
                $hisLabServeModel->type_charge = 0;
                $hisLabServeModel->create_dt = date('Y-m-d H:i:s');
                $hisLabServeModel->ordername = $spinD->fullname();
                $hisLabServeModel->grant_type = $order->get('charge_type') ? $order->get('charge_type') : '';
                $hisLabServeModel->is_tpl = strtoupper($order->get('charge_type'))  === 'PHIC' ? 0 : 1 ;
                $hisLabServeModel->history = "Create: " . date('Y-m-d H:i:s') . " [personnel_id: " . auth()->user()->personnel_id . "]";

                if (!$hisLabServeModel->save()) {
                    throw new EhrException('Lab orders not saved.');
                }

                HisGrantRequest::query()->where('ref_no', $refno)->where('ref_source',self::REF_SOURCE)->forceDelete();

                foreach ($orders['orders'] as $key => $order) {
                    if ($order['kardexGroup'] == 'Diagnostic') {
                        $orderD = DiagnosticOrderLab::query()->where('id', $order['id'])->first();
                        if($orderD){
                            $hisLabServeDetialsModel = new HisLaboratoryServeDetails();
                            $hisLabServeDetialsModel->refno = $refno;
                            $hisLabServeDetialsModel->service_code = $orderD->service_id;
                            $hisLabServeDetialsModel->request_doctor = $orderD->doctor_id;
                            $hisLabServeDetialsModel->manual_doctor = $dr_name;
                            $hisLabServeDetialsModel->request_dept = $doc_area;
                            $hisLabServeDetialsModel->is_in_house = 1;
                            $hisLabServeDetialsModel->quantity = 1;
                            $hisLabServeDetialsModel->request_flag = strtolower($orderD->transaction_type) == 'phic' ? 'phic' : null;
                            $hisLabServeDetialsModel->price_cash = $orderD->price_cash;
                            $hisLabServeDetialsModel->price_cash_orig = $orderD->price_cash_orig;
                            $hisLabServeDetialsModel->price_charge = $orderD->price_charge;
                            $hisLabServeDetialsModel->clinical_info = $orderD->impression;
                            $hisLabServeDetialsModel->status = DiagnosticOrderLab::STATUS_PENDING;
                            $hisLabServeDetialsModel->create_id = auth()->user()->username;
                            $hisLabServeDetialsModel->create_dt = date('Y-m-d H:i:s');
                            $hisLabServeDetialsModel->history = "Create request from Diagnostic EHR[Mobile] " . date('Y-m-d H:i:s') . " " . auth()->user()->username;

                            if (!$hisLabServeDetialsModel->save()) {
                                throw new EhrException('Lab orders details not saved.');
                            }


                            if($orderD->price_cash == 0){
                                $grantReq = new HisGrantRequest();
                                $grantReq->ref_no = $refno;
                                $grantReq->ref_source = self::REF_SOURCE;
                                $grantReq->service_code = $orderD->service_id;
                                $grantReq->save();

                                HisLaboratoryServeDetails::query()->where('refno', $refno)->where('service_code',$orderD->service_id)
                                ->update([
                                    'request_flag'  => self::REQUEST_FLAG_CHARITY,
                                    'history'       => DB::raw("CONCAT(history,  'Create request details [".self::REQUEST_FLAG_CHARITY."] at laboratory-social service  " . date('Y-m-d H:i:s') . " " . auth()->user()->username . "\n' )"),
                                    'modify_id'     => auth()->user()->username,
                                    'modify_dt'     => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }

                DiagnosticOrderLab::query()
                    ->where('order_batchid', '=', $orders['id'])
                    ->update(['refno' => $refno]);


                $hisLabServe['details'] = $details;
                $data[] = $hisLabServe;
            }
        }


        return [
            'msg' => 'HIS Laboratory Order saved',
            'hisbatch' => $data,
        ];
    }

    public function getTracker()
    {
        $refno = HisLabTracker::query()
            ->lockForUpdate()
            ->first();

        return $refno;
    }

    public function getReleaseLabTracker()
    {
        $command = $this->hisDb->createCommand();
        $command->select("RELEASE_LOCK('last_refno') as lock_state");
        $command->from('seg_lab_tracker');

        return $command->queryScalar();
    }


    public static function getNewTracker()
    {
        DB::connection('his_mysql')->select( DB::raw("SELECT GET_LOCK('last_refno', 10)") );

        $refno = HisLabTracker::query()
            // ->lockForUpdate()
            ->first();

        $last_refno = $refno->last_refno + 1;
        $refno->update([
            'last_refno' => $last_refno
        ]);

        DB::connection('his_mysql')->select( DB::raw("SELECT RELEASE_LOCK('last_refno')") );
        return $last_refno;
    }
}
