<?php


namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;

use App\Models\AreaCatalog;
use App\Models\AuxiliaryServiceCatalog;
use App\Models\AuxiliaryServiceOrder;
use App\Models\BatchOrderNote;
use App\Models\CoursewardOrder;
use App\Models\DepartmentsCatalog;
use App\Models\DeptEncounter;
use App\Models\DiagnosticOrderLab;
use App\Models\DiagnosticOrderRad;
use App\Models\DischargeOrder;
use App\Models\Encounter;
use App\Models\FrequencyCatalog;
use App\Models\HIS\HisBillingEncounter;
use App\Models\HIS\HisCharityGrants;
use App\Models\HIS\HisEncounter;
use App\Models\HIS\HisEncounterInsuranceMemberInfo;
use App\Models\HIS\HisReferral;
use App\Models\LabService;
use App\Models\MedsOrder;
use App\Models\Mongo\OrderNotes;
use App\Models\Order\Diagnostic\AuxiliaryService;
use App\Models\Order\Diagnostic\LaboratoryService;
use App\Models\Order\Diagnostic\RadiologyService;
use App\Models\Order\Diagnostic\ServicePackage;
use App\Models\PersonnelAssignment;
use App\Models\PersonnelCatalog;
use App\Models\PersonnelPermission;
use App\Models\PhilMedicine;
use App\Models\RadioService;
use App\Models\ReferralOrder;
use App\Models\RepetitiveOrder;
use App\Services\Doctor\Soap\SoapService;
use App\Services\HIS\HisPhicCoverage\HisDiscountService;
use App\Services\HIS\HisPhicCoverage\PhicCoverageService;
use App\Services\Patient\PatientService;
use App\Services\Personnel\PersonnelService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use function MongoDB\BSON\toJSON;
use PDO;
use Illuminate\Support\Str;
use App\Models\EncounterCourseward;
use App\Models\ReferralPurposeCatalog;
use App\Models\ServicePackageOrder;
use App\Models\SmedDiagnosisProcedure;
use App\Models\SmedRepetitiveSession;
use App\Services\Doctor\Permission\PermissionService;
use DateTime;
use Psy\Util\Json;
use App\Exceptions\His\HisActiveResource;

class DoctorOrderService extends PatientService
{

    /**
     * @var Encounter $encouter
     */
    public $encounter;

    /**
     * @var string
     */
    protected $encounter_no;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array|null
     */
    protected $id;

    /**
     * @var array|null
     */
    protected $doctor_id;

    /**
     * @var array|null
     */
    protected $order_date;

    private $impression_remark;

    /** @var PermissionService $permService */
    public $permService;

    public $batch_has_diag_labs = false;
    public $batch_has_diag_rad = false;

    function __construct(Encounter $encounter = null)
    {
        $this->encounter = $encounter;
        $this->permService = new PermissionService($encounter);
    }

    public static function init($encounter)
    {
        $encounter = Encounter::query()->find($encounter);
        if (is_null($encounter)) {
            throw new EhrException('Encounter does not exist.', 404);
        }

        return new DoctorOrderService($encounter);
    }

    /**
     * @param DiagnosticOrderLab|DiagnosticOrderRad $diagnosticModel
     * @return String $impression
     */
    public function getImpression($diagnosticModel = null)
    {

        if(!is_null($this->impression_remark))
            return $this->impression_remark;

        $soap = SoapService::init($this->encounter->encounter_no);

        #soap_assessment_clinical_imp_defined
        $impression_original = $soap->getClinicalImpression(
                $this->encounter->encounter_no
        );

        $impression = '';
        if ($this->encounter->currentDeptEncounter->deptenc_code == 'IPE') {
            if (!empty($diagnosticModel->impression)) {
                $impression = $diagnosticModel->impression;
            } else {
                $impression = $impression_original['admitting_diag'];
            }
        } else {
            if($diagnosticModel)
                $impression = $diagnosticModel->impression;
        }

        $this->impression_remark = $impression;
        return $impression;

    }

    private function setImpression($impression)
    {
        $this->impression_remark = $impression;
    }


    private function validatePrescriptionOrders(Collection $data)
    {
        // $enc_type = $this->encounter->deptEncounters[0]->deptenc_code;
        foreach ($data->get('orders') as $key => $order) {
            $orDate = date('Y-m-d', $order->get('date'));
            $orTime = $order->get('time');

            $this->validateDate(strtotime($orDate.' '.$orTime));

        }

    }

    private function validateDate($orderDate){
        $enc_date = strtotime($this->encounter->encounter_date);
        if ($orderDate < $enc_date) {
            // throw new EhrException('errorOrderDate'.strtoupper($enc_type));
            throw new EhrException('Order date must equal or greater than the admission date and time.');
        }
        if ($orderDate > time() ) {
            // throw new EhrException('errorOrderDate'.strtoupper($enc_type));
            throw new EhrException('Order date must equal or less than the current date and time.');
        }
    }

    /**
     * @param array $data
     *
     * @throws CDbException
     */
    public function saveMedicationOrders($data)
    {
        /**
         * @var Collection $data
         */
        $data = collect($data)->recursive();


        $this->validatePrescriptionOrders($data);


        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();

        MedsOrder::query()->where('order_batchid', $batchOrder->id)->forceDelete();

        if(count($data->get('orders')) <= 0)
            return $this->_getPrescriptionResponse($batchOrder);



        foreach ($data->get('orders') as $orderData) {
            /**
             * @var Collection $orderData
             */
            if (empty($orderData)) {
                continue;
            }

            $order = new MedsOrder();
            $order->item_id = null;

            $order->custom_item = trim(@$orderData->get('custom_item'));
            $order->is_custom = @$orderData->get('is_custom',0);

            $order->order_batchid = $batchOrder->id;
            $order->order_groupid = 3;

            $order->frequency_id = null;
            if (!empty($orderData->get('frequency'))) {
                $freq = FrequencyCatalog::query()->where('short_desc', @$orderData->get('frequency'))->first();
                $order->frequency_id = $freq ? $freq->id : null;
            }

            $drugCode = PhilMedicine::query()->find(@$orderData->get('item'));
            $drugItem = PhilMedicine::query()->where('description', @$orderData->get('item'))->first();

            $order->id = Str::uuid();
            $order->sig = @$orderData->get('sig');
            $order->method = @$orderData->get('method');
            $order->route = @$orderData->get('route');
            $order->dose = @$orderData->get('dosage');
            $order->preparation = @$orderData->get('preparation');
            $order->route = @$orderData->get('route');
            $order->timing = @$orderData->get('timing');
            $order->order_dt = date('YmdHis');
            $order->duration = @$orderData->get('duration_amount').@$orderData->get('duration_interval');
            $order->quantity = @$orderData->get('quantity');
            $order->remarks = @$orderData->get('remarks','');
            $order->is_stat = @$orderData->get('stat') ? 1 : 0;
            $order->is_cash = @$orderData->get('stat') ? 1 : 0;
            $order->drug_code = (is_null($drugCode) && is_null($drugItem)) ? 'NONE' : (is_null($drugCode)? $drugItem->drug_code : (!empty($drugCode->drug_code) ? $drugCode->drug_code : $drugItem->drug_code));
            $order->encounter_no = $this->encounter->encounter_no;
            $order->is_ehr = 1;
            $order->order_date = date('Ymd', @$orderData->get('date'));
            $order->order_time = @$orderData->get('time');
            $order->order_check = @$orderData->get('check') ? 1 : 0;

            if (!$order->save()) {
                throw new EhrException('Failed to save medication order');
            }
        }

        return $this->_getPrescriptionResponse($batchOrder);
    }


    private function _getPrescriptionResponse(BatchOrderNote $batchOrder)
    {
        $batchOrder = BatchOrderNote::query()->find($batchOrder->id);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'msg' => 'Prescription Saved.',
                'batch'    => $data,
        ];
    }

    /**
     * Returns the current unfinalized order note for the current user. If
     * no such record currently exists it is created.
     *
     * @return BatchOrderNote The current unfinalized order note record
     *
     * @throws EhrException if the new order note could not be successfully created
     *
     * @todo Do cheap-caching for the returned unfinalized order to optimize multiple successive calls
     */
    public function getCurrentUnfinalizedBatchOrderNote()
    {
        $note = $this->getUnfinalizedBatchOrderNote();
        if (!$note) {
            $note = new BatchOrderNote();

            $note->id = Str::uuid();
            $note->encounter_no = $this->encounter->encounter_no;
            $note->is_finalized = 0;
            $note->doctor_id = auth()->user()->personnel->personnel_id;
            $note->encoder_id = auth()->user()->id;
            $note->create_dt = date('YmdHis');
            $note->modify_dt = date('YmdHis');

            if (!$note->save()) {
                throw new EhrException('Failed to create a new batch order note');
            }
        }

        return $note;
    }

    public function getTypeCharge(){
        $his = HisActiveResource::instance();
        $result = $his->getTypeCharge();
        if(is_null($result)){
            throw new EhrException('Unable to fetch data.');
        }
        return $result;
    }

    /**
     * @return BatchOrderNote|null
     */
    public function getUnfinalizedBatchOrderNote()
    {
        return $this->encounter->unfinalizedBatchOrderNote;
    }


    /**
     * @param array $options
     *
     * @return BatchOrderNote[]
     */
    public function getBatchOrders($options = [])
    {
        return $this->encounter->batchOrderNotes;
    }

    /**
     * @param array $options
     *
     * @return Collection
     */
    public function getOrderNotesProvider($options = [])
    {
        return OrderNotes::query()->where('encounterNo', $this->encounter->encounter_no)->get();
    }


    private function getPlanMNotes()
    {
        return $this->getOrderNotesProvider()->map(
            function ($note, $key) {
                unset($note['_id']);

                return $note;
            }
        )->toArray();
    }


    /**
     * @param BatchOrderNote $batchOrder
     * @param array $notes
     * @param array $data
     * @return Collection $data
     */
    public function getThisBatchOrder(BatchOrderNote $batchOrder, $notes, $data = [])
    {
        $medicationOrder = $batchOrder->medsOrders()->first();

        $laboratoryOrder = $batchOrder->diagnosticOrderLabs()->first();

        $radiologyOrder = $batchOrder->diagnosticOrderRads()->first();

        $coursewardOrder = $batchOrder->courseWards()->first();

        $referralOrder = $batchOrder->referralOrders()->first();

        $dischargeOrder = $batchOrder->dischargeOrders()->first();

        $repetitiveOrder = $batchOrder->repetitiveOrder()->first();

        if($laboratoryOrder)
            $this->batch_has_diag_labs = true;

        if($radiologyOrder)
            $this->batch_has_diag_rad = true;

        if ($medicationOrder || $laboratoryOrder || $radiologyOrder || $coursewardOrder || $referralOrder || $dischargeOrder || $repetitiveOrder) {
            $row = [
                    'id'         => $batchOrder->id,
                    'finalized'  => $batchOrder->is_finalized == 1,
                    'created'    => strtotime($batchOrder->create_dt),
                    'doctorId'   => $batchOrder->doctor_id,
                    'doctor'     => $batchOrder->doctor == null ? '' : $batchOrder->doctor->getFullNameWithHonorific(),
                    'discharged' => $batchOrder->encounterNo->is_discharged,
                    'encounter_no' => $batchOrder->encounter_no
            ];

            $orders = [];

            /**
             * Medication orders
             */
            $orders = $this->getAllMedsOrders($batchOrder, $orders);

            /**
             * Diagnostic orders (Laboratory)
             */

            $orders = $this->getAllDiagnosticLabsOrders($batchOrder, $orders);

            /**
             * Diagnostic orders (Radiology)
             */


            $orders = $this->getAllDiagnosticRadsOrders($batchOrder, $orders);


            /**
             * Diagnostic orders (Auxiliary services)
             */

            $orders = $this->getAllDiagnosticAuxiliaryServiceOrders($batchOrder, $orders);


            /**
             * Course Ward orders (Course Wards)
             */
            $orders = $this->getAllCoursewards($batchOrder,$orders);

            /**
             * Package orders
             */
            $orders = $this->getAllServicePackageOrders($batchOrder,$orders);


            /**
             * Discharge order (Only one per order batch)
             */

            $orders = $this->getAllDischargeOrders($batchOrder,$orders);



            /**
             * Referral order (Only one per order batch)
             */

            $orders = $this->getAllReferralOrders($batchOrder, @$orders);


            /**
             * Repetitive orders
             */
            $orders = $this->getAllRepetitiveOrders($batchOrder, @$orders);


            /**
             * Doctor Notes
             */
            $row['notes'] = array_values(
                    array_filter(
                            $notes,
                            function ($note) use ($batchOrder) {
                                return $note['orderBatch'] == $batchOrder->id;
                            }
                    )
            );

            $row['orders'] = $orders;

            $data[] = $row;

        }

        return collect($data);
    }

    public function getAllOrders()
    {
        $batchOrders = $this->getBatchOrders();


        $notes = $this->getPlanMNotes();


        $data = [];

        // $pharmacyArea = AreaCatalog::query()->where('area_code', 'IP')->first();

        // $encounter_type = DeptEncounter::query()->where('encounter_no', $this->encounter->encounter_no)->first();

        foreach ($batchOrders as $batchkey => $batchOrder) {

            $data = $this->getThisBatchOrder($batchOrder, $notes, $data);

        }

        return $data;
    }

    public function getInsuranceDetails()
    {
        $phicCoverageService = new PhicCoverageService($this->encounter);
        $discount = (new HisDiscountService($this->encounter))->execute();
        $soap = (new SoapService($this->encounter))->getSoap()['clinical_imp'];
        $insurance_memeber_info = HisEncounterInsuranceMemberInfo::where('encounter_nr', $this->encounter->encounter_no)
            ->with('member_category')->first();

        return [
            'phic_no' => $insurance_memeber_info ? $insurance_memeber_info->insurance_nr : '',
            'category'  => $insurance_memeber_info ? $insurance_memeber_info->member_category->memcategory_desc : '',
            'phic_coverage'  => $insurance_memeber_info ? $phicCoverageService->getPhicCoverage() : '',
            'discounts' => [
                'is_percentage' => empty($discount['discount']) ? 0 : 1,
                'classification' => $discount['discountid'] ? $discount['discountid'] : '',
                'discount' => $discount['discount_amount']
                    ? number_format($discount['discount_amount'], 2, '.', '')
                    : $discount['discount'] ? number_format($discount['discount'], 2, '.', '') : ''
            ],
            'clinical'  => $soap['clinical_imp'],
        ];

    }


    public function getAllDiagnosticLabsOrders(BatchOrderNote $batchOrder, $orders = [], DiagnosticOrderLab $order = null)
    {

        foreach ($batchOrder->diagnosticOrderLabs as $lab) {
            /**
             * @var DiagnosticOrderLab $lab
             */
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = $lab->is_served == 1 ? 'Served' : 'Not Served';
            }


            $labService = LabService::where('service_id', $lab->service_id)->with('group')->first()->toArray();
            $dt = [$labService['group_id']];

            if (in_array("B", $dt)) {
                $costCenters = "BLOODBANK";
            } elseif (in_array("ECHO", $dt)) {
                $costCenters = "SPECIAL";
            } else {
                $costCenters = "LABORATORY";
            }

            if (in_array("B", $dt)) {
                $costCentersRef = "BLOODBANK";
            } elseif (in_array("ECHO", $dt)) {
                $costCentersRef = "SPECIAL LABORATORY";
            } elseif (in_array("SPC", $dt)) {
                $costCentersRef = "SPECIAL LABORATORY";
            } elseif (in_array("SPL", $dt)) {
                $costCentersRef = "SPECIAL LABORATORY";
            } elseif (in_array("CATH", $dt)) {
                $costCentersRef = " SPECIAL LABORATORY";
            } else {
                $costCentersRef = "LABORATORY";
            }

            $clinical = $this->getImpression($lab);

            @$orders[] = [
                'id'            => $lab->id,
                'kardexGroup'   => 'Diagnostic',
                'item'          => (new LaboratoryService($labService))->toArray(),
                'requestDate'   => strtotime($lab->order_dt),
                'stat'          => $lab->is_stat == 1,
                'cash'          => $lab->is_cash == 1,
                'charge'        => $lab->is_cash != 1,
                'charge_type'   => $lab->transaction_type,
                'remarks'       => $lab->comments,
                'status'        => $status,
                'frequency'     => $lab->freq_id ? $lab->freq_id : '',
                'frequency_details' => $lab->frequency ? $lab->frequency->frequency_disc : '',
                'is_monitor'    => $lab->is_monitor == 1,
                'qty'           => $lab->quantity,
                'clinical'      => $clinical,
                'sname'         => $costCenters,
                'servicesID'    => 'LABORATORY:' . $lab->service_id,
                'is_blood'      => $costCenters == 'BLOODBANK' ? 1 : 0,
                'encounterNo'   => $batchOrder->encounter_no,
                'is_servicesID' => $lab->service_id,
                'refno'         => $lab->refno,
                'kardexGroupRef' => empty($lab->refno) ? $costCentersRef : $costCentersRef.' - Reference # '.$lab->refno,
                'encounterCourseWardID'   => $batchOrder->encounterCoursewardOrder->id,
                'impression' => $lab->impression,
                'order_date' => $lab->order_dt,

            ];
        }
        return @$orders;
    }

    public function getAllDiagnosticRadsOrders(BatchOrderNote $batchOrder, $orders = [], DiagnosticOrderRad $order = null)
    {
        foreach ($batchOrder->diagnosticOrderRads as $rad) {
            /**
             * @var DiagnosticOrderRad $rad
             */
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = $rad->is_served == 1 ? 'Served' : 'Not Served';
            }

            $clinical = $this->getImpression($rad);

            $radService = RadioService::query()->find($rad->service_id);
            @$orders[] = [
                'id'          => $rad->id,
                'kardexGroup' => 'Diagnostic',
                'item'        => (new RadiologyService($radService))->toArray(),
                'requestDate' => strtotime($rad->order_dt),
                'stat'        => $rad->is_stat == 1,
                'cash'        => $rad->is_cash == 1,
                'charge'      => $rad->is_cash != 1,
                'charge_type' => $rad->transaction_type,
                'remarks'     => $rad->remarks,
                'status'      => $status,
                'clinical'    => $clinical,
                'sname'       => 'RADIOLOGY',
                'servicesID'  => 'RADIOLOGY:' . $rad->service_id,
                'encounterNo' => $batchOrder->encounter_no,
                'refno'         => $rad->refno,
                'kardexGroupRef'    => 'Radiology - Reference # ' . $rad->refno,
                'encounterCourseWardID'   => $batchOrder->encounterCoursewardOrder->id,
                'impression' => $rad->impression,
                'order_date' => $rad->order_dt,
            ];
        }
        return @$orders;
    }


    public function getAllDiagnosticAuxiliaryServiceOrders(BatchOrderNote $batchOrder, $orders = [], AuxiliaryServiceOrder $order = null)
    {
        foreach ($batchOrder->auxiliaryServiceOrders as $aux) {
            /**
             * @var AuxiliaryServiceOrder $aux
             */
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
                if ($aux->isCarriedOut()) {
                    $status = 'Verified';
                }
            }
            $auxService = AuxiliaryServiceCatalog::query()->find($aux->service_id);
            @$orders[] = [
                'id'          => $aux->id,
                'kardexGroup' => 'Diagnostic',
                'item'        => (new AuxiliaryService($auxService))->toArray(),
                'requestDate' => strtotime($aux->due_dt),
                'stat'        => $aux->is_stat == 1,
                'reason'      => $aux->remarks,
                'status'      => $status,
            ];
        }

        return @$orders;
    }


    public function getAllServicePackageOrders(BatchOrderNote $batchOrder, $orders = [], ServicePackageOrder $order = null)
    {
        foreach ($batchOrder->servicePackageOrders as $package) {
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
                if ($package->isCarriedOut()) {
                    $status = 'Verified';
                }
            }
            $servicePackage = ServicePackage::instance($package->package_code);
            if (!$servicePackage) {
                continue;
            }

            $clinical = $this->getImpression($package);

            $orders[] = [
                'id'          => $package->id,
                'kardexGroup' => 'Diagnostic',
                'item'        => $servicePackage->toArray(),
                'requestDate' => strtotime($package->order_dt),
                'stat'        => 0,
                'cash'        => $package->is_cash,
                'reason'      => $package->remarks,
                'status'      => $status,
                'sname'       => 'MISCELLANEOUS',
                'servicesID'  => 'PACKAGE:' . $package->package_code,
                'clinical'    => $clinical,
                'qty'         => $package->quantity,
            ];
        }

        return @$orders;
    }



    public function getAllDischargeOrders(BatchOrderNote $batchOrder, $orders = [], DischargeOrder $order = null)
    {
        if ($batchOrder->dischargeOrder) {
            /**
             * @var DischargeOrder $order
             */
            $order = $batchOrder->dischargeOrder;
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
                if ($order->isCarriedOut()) {
                    $status = 'Verified';
                }
            }

            $obj = json_decode($order->discharge_order);
            $dpt = AreaCatalog::query()->find($obj['department']);

            $orders[] = collect([
                'id'          => $order->id,
                'kardexGroup' => 'Discharge',
                'date'        => strtotime($order->discharge_dt),
                'dateOPD'     => strtotime($order->follow_up_dt),
                'er_nod'      => $order->er_nod,
                'status'      => $status,
                // 'is_deleted'  => $is_deleted,
                'kardexGroupRef' => 'Discharge',
            ])->merge($order->getDetails());
        }


        return @$orders;
    }



    public function getAllRepetitiveOrders(BatchOrderNote $batchOrder, $orders = [], RepetitiveOrder $order = null)
    {
        $procduleList = collect(RepetitiveService::getProcedure());
        $encounterCourseWardCount = 0;
        $courswards = collect($batchOrder->encounterCoursewardOrders->toArray());
        $list = [];
        foreach ($batchOrder->repetitiveOrder as $rep) {
            /**
             * @var RepetitiveOrder $rep
             */
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
            }

            /**
             * getting specific courseward based on
             * session start date and time
             * remarks
             * procedure value
             *
             * algorithm was built through this compared to yii, since laravel has different sequencing on sorting results compared to yii
             *
             * rather then webapp yii code applied
             * isset($batchOrder->encounterCoursewardOrders[$encounterCourseWardCount]['id']) ? $batchOrder->encounterCoursewardOrders[$encounterCourseWardCount]['id'] : '',
             *
             * encounterCoursewardOrders was just based on batchorder_id and not sorted, even if  it will be sorted based on
             * order_date, it would be in accourate in some cases if sessions orders has the same date and time.
             */
            $coursward = $courswards->filter(function($value, $key) use ($rep){
                $sessionStartDateAndTime = $rep->session_start_date .' '. $rep->session_start_time;

                return $value['order_date'] == $sessionStartDateAndTime &&
                        preg_match("~\b".$rep->remarks."\b~", $value['action']) == 1  &&
                        preg_match("~\b".$rep->procedure_value."\b~", $value['action']) == 1;
            })->first();

            $orders[] = [
                    'id'                      => $rep->id,
                    'kardexGroup'             => 'Repetitive',
                    'status'                  => $status,
                    'procedure'               => $rep->repetitive_procedure,
                    'procedure_value'         => $procduleList->where('id',$rep->repetitive_procedure)->first()['procedure'],
                    'procedure_remarks'       => $rep->remarks,
                    'requestDateSessionStart' => strtotime($rep->session_start_date),
                    'requestDateSessionEnd'   => strtotime($rep->session_end_date),
                    'requestTimeSessionStart' => $rep->session_start_time,
                    'requestTimeSessionEnd'   => $rep->session_end_time,
                    'kardexGroupRef'          => 'Repetitive Session',
                    'encounterNo'             => $batchOrder->encounterNo->encounter_no,
                    'encounterCourseWardID'   => is_null($coursward)?  '' : $coursward['id'],
                    'displaySessionStartTime' => date('h:i a', strtotime($rep->session_start_time)),
                    'displaySessionEndTime'   => date('h:i a', strtotime($rep->session_end_time)),
            ];
            $encounterCourseWardCount++;
        }
        return @$orders;
    }


    public function getAllMedsOrders(BatchOrderNote $batchOrder, $orders = [])
    {

        foreach ($batchOrder->medsOrders as $med) {
            /***
             * @var MedsOrder $med
             */
            $status = 'Pending';
            if (!($med->is_ehr)) {
                if ($batchOrder->is_finalized) {
                    $status = $med->is_served == 1 ? 'Served' : 'Not Served';
                }
            } else {
                if ($batchOrder->is_finalized) {
                    $status = 'Finalized';
                }
            }


            $des = PhilMedicine::query()->where('drug_code', $med->drug_code)->first();

            @$orders[] = [
                    'id'                => $med->id,
                    'kardexGroup'       => 'Pharmacologic/Medication',
                'item'              => $des ? (
                    $des->drug_code ? [
                        'id'   => $des->description,
                        'code' => $des->drug_code,
                        'name' => $des->description,
                ] : ((!empty($med->item->item_name) && $med->is_custom == 0) ? $med->item->item_name : $med->custom_item))
                : ((!empty($med->item->item_name) && $med->is_custom == 0) ? $med->item->item_name : $med->custom_item),
                    'obj_type' => $des ?($des->drug_code ? 'object':'string'):'string',
                    'custom_item'       => $med->custom_item,
                    'is_custom'         => $med->is_custom,
                    'frequency'         => $med->frequencyCatalog ? $med->frequencyCatalog->short_desc : null,
                    'quantity'          => (int)$med->quantity,
                    'sig'               => $med->sig,
                    'dosage'            => $med->dose,
                    'method'            => $med->method,
                    'preparation'       => $med->preparation,
                    'timing'            => $med->timing,
                    'route'             => $med->route,
                    'duration_amount'   => $med->duration ? substr($med->duration, 0, -1) : null,
                    'duration_interval' => $med->duration ? substr($med->duration, -1) : null,
                    'order_date'        => $med->order_dt ? strtotime($med->order_dt) : null,
                    'remarks'           => $med->remarks,
                    'status'            => $status,
                    'stat'              => $med->is_stat == 1,
                    'cash'              => $med->is_cash == 1,
                    'refno'             => $med->refno,
                    'kardexGroupRef'    => $med->is_ehr == 1 ? 'Prescription' : 'Pharmacologic/Medication - Reference # '.$med->refno,
                    'is_ehr'            => $med->is_ehr,
                    'order_date_new'    => strtotime($med->order_date),
                    'order_time'        => $med->order_time,
                    'order_check'       => $med->order_check == 1 ? true : false,
                    'order_time_ampm'   => date('h:i a', strtotime($med->order_time))
            ];
        }

        return @$orders;
    }


    public function getAllReferralOrders(BatchOrderNote $batchOrder, $orders = [], ReferralOrder $order = null)
    {
        if ($batchOrder->referralOrder) {
            /**
             * @var ReferralOrder $order
             */
            if(is_null($order))
                $order = $batchOrder->referralOrder;
            $status = 'Pending';
            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
                if ($order->isCarriedOut()) {
                    $status = 'Verified';
                }
            }

            @$orders[] = collect(
                    [
                            'id'             => $order->id,
                            'kardexGroup'    => 'Referral',
                            'status'         => $status,
                            'order_date'     => strtotime($order->create_dt),
                            'is_deleted'     => $order->is_deleted,
                            'kardexGroupRef' => 'Referral',
                            'referto_doctorid' => $order->referto_doctorid,
                            'create_dt' => $order->create_dt,
                            'create_id' => $order->create_id

                    ]
            )->merge(json_decode($order->data));
        }

        return @$orders;
    }

    public function getAllCoursewards(BatchOrderNote $batchOrder, $orders = [], CoursewardOrder $course = null)
    {

        foreach ($batchOrder->courseWards as $courseWard) {

            $status = 'Verified';

            if ($batchOrder->is_finalized) {
                $status = 'Finalized';
            }

            if(!is_null($course)){
                if($courseWard->id != $course->id)
                    $course = CoursewardOrder::query()->find($courseWard->id);
            }
            else
                $course = CoursewardOrder::query()->find($courseWard->id);

            @$orders[] = [
                    'id'             => $course->id,
                    'kardexGroup'    => 'Doctor Order / Action',
                    'remarks'        => $course->action,
                    'requestDate'    => strtotime($course->order_dt),
                    'status'         => $status,
                    'encounterNo'    => $course->orderBatch->encounter_no,
                    'kardexGroupRef' => 'Doctor\'s Order / Action',
                    'order_date'     => $course->order_date != null ? strtotime(
                            $course->order_date
                    ) : strtotime($course->order_dt),
                    'order_time'     => $course->order_time,
                    'order_checked'  => $course->order_checked == 1 ? true : false,
            ];
        }

        return @$orders;
    }

    public function saveRepetitiveSession($data)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();

        $data = collect($data);

        $dtStart = date('Y-m-d', $data->get('requestDateSessionStart_session'));
        $dtEnd = date('Y-m-d', $data->get('requestDateSessionEnd_session'));

        $enc_dt =strtotime($this->encounter->encounter_date);

        $startTime = $data->get('requestTimeSessionStart_session');
        $endTime = $data->get('requestTimeSessionEnd_session');

        $sessionStartDate = strtotime($dtStart . ' ' . $startTime);
        $sessionEndDate = strtotime($dtEnd . ' ' . $endTime);

        if ($sessionStartDate < $enc_dt) {
            throw new EhrException('Session Start Date must greater than Encounter Date', 500);
        } elseif ($sessionEndDate < $enc_dt) {
            throw new EhrException('Session End Date must greater than Encounter Date', 500);
        } elseif ( $sessionEndDate < $sessionStartDate) {
            throw new EhrException('Session End Date must greater than Session Start Date', 500);
        }


        $model = RepetitiveOrder::query()->find($data->get('id'));

        if (is_null($model)) {
            $model = new RepetitiveOrder();
            $model->id = Str::uuid();
            $model->order_batchid = $batchOrder->id;
            $model->history = 'Created By: '.auth()->user()->personnel_id.' '.date("F j,Y h:i a");
            $model->create_id = auth()->user()->personnel_id;
            $model->create_dt = date('Y-m-d h:i:s');
        }

        $model->repetitive_procedure = $data->get('procedure_session');
        $model->remarks = $data->get('procedure_remarks_session');
        $model->session_start_date = date('YmdHis', $data->get('requestDateSessionStart_session'));
        $model->session_end_date = date('YmdHis', $data->get('requestDateSessionEnd_session'));
        $model->session_start_time = $data->get('requestTimeSessionStart_session');
        $model->session_end_time = $data->get('requestTimeSessionEnd_session');

        $model->modify_id = auth()->user()->personnel_id;
        $model->modify_dt = date('Y-m-d h:i:s');

        if (!$model->save()) {
            throw new EhrException('Failed to save Repetitive Order');
        }


        $batchOrder = BatchOrderNote::query()->find($batchOrder->id);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'Repetitive Session is Successfully Saved!',
                'batch'    => $data,
        ];
    }


    /**
     * @param Collection $data
     * @param String $type [rad,lab]
     */
    private function validateDiagnosticOrder($data, $type = 'lab')
    {
        $count = 0;
        $stat = false;
        $diagnosis = null;

        if(count($data)<=0)
            throw new EhrException('No orders to save.');

        foreach ($data as $value) {
            /**
             * @var Collection $value
             */

            if(!($value instanceof Collection))
                throw new EhrException('Invalid orders type instance "'.(gettype($value)).'"', 500, $data->toArray(), true);

            if ($value->get('name')) {
                $diagnosis = $value->get('name');
            }

            if(is_null($this->impression_remark)){
                $model = null;
                switch ($type) {
                    case 'rad':
                        $model = new DiagnosticOrderRad();
                        break;
                    case 'lab':
                    default:
                        $model = new DiagnosticOrderLab();
                        break;
                }
                $impression = $model::query()->find($value->get('id'));
                //================== update impression ==================
                $clinical_impression = $this->getImpression($impression);
                if($value->get('clinical') != $clinical_impression){
                    $this->setImpression($value->get('clinical'));
                }
                //================== update impression ==================
            }
            $clinical[$count] = $this->impression_remark;



            if ($value->get('stat')) {
                $stat = $value->get('stat');
            }

            if ($value->get('remarks')) {
                $comments = $value->get('remarks');
            }
            $count++;
        }

        foreach ($clinical as $val) {
            if($val) {
                $value = $val;
            }
        }

        if ($diagnosis && $value) {
            if($stat) {
                if($comments) ;
                else {
                    throw new EhrException('comments');
                }
            }
        } else {
            throw new EhrException('clinical');
        }
    }

    public function saveDiagnosticLabOrders($data)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $data = collect($data)->recursive();

        // clinical impression to be used will also be initialize from this function
        $this->validateDiagnosticOrder($data, 'lab');

        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();
        $batchOrder = BatchOrderNote::query()->find($batchOrder->id);
        // remove all previous order in this current batch order
        $batchOrder->deleteLabOrder();

        $service_ids = [];
        foreach ($data as $key => $lab) {
            $service_ids[] = explode(':', $lab->get('servicesID'))[1];
        }

        $services = collect(LaboratoryService::getLabServices($this->encounter, $service_ids));
        $monitorings = collect(LaboratoryService::getMonitoring());

        $index = 0;
        foreach ($data as $key => $lab) {
            /** @var Collection $lab */
            switch ($lab->get('sname')) {
                case "BLOODBANK":
                    $tag = config('app.diagnosic_bloodbank');
                    break;
                case "SPECIAL LABORATORY":
                    $tag = config('app.diagnosic_special_laboratory');
                    break;
                default:
                    $tag = config('app.diagnosic_laboratory');
                    break;
            }
        
            $service_id = explode(':', $lab->get('servicesID'))[1];
            $service = collect($services->where('id', $lab->get('servicesID'))->first());

            $monitoring = null;
            if($lab->get('freq_id')){
                $monitoring  = collect($monitorings->where('frequency_code', $lab->get('freq_id'))->first());
                if(!$monitoring)
                    throw new EhrException('Monitoring option does not exist.');
            }

            $model = new DiagnosticOrderLab();
            $model->id = Str::uuid();
            $model->order_batchid = $batchOrder->id;
            $model->order_groupid = 2;
            $model->order_dt = date('Y-m-d H:i:s');
            $model->encounter_no = $this->encounter->encounter_no;
            $model->doctor_id = auth()->user()->personnel_id;
            $model->service_id = $service_id;
            $model->quantity = 1;
            $model->is_served = 0;
            $model->is_checked = $lab->get('check') ? 1  : 0;
            $model->is_stat = $lab->get('stat');
            $model->is_cash = $lab->get('cash');
            $model->price_cash = $service->get('net_price');
            $model->price_cash_orig = $service->get('price_cash');
            $model->price_charge = $service->get('price_charge');
            $model->transaction_type = $lab->get('charge_type'); // this is the charge type which variable name to transaction_type
            $model->freq_id = $lab->get('freq_id') ?? null; // monitoring optional
            $model->is_monitor = $lab->get('freq_id') ? 1  : 0; // set 1 if freq_id is not null
            $model->due_dt = date('YmdHis', $lab->get('requestDate'));    /*Due Date is Order Date in UI*/
            $model->comments = $lab->get('remarks');   /*Remarks is Comments in UI*/
            $model->impression = $lab->get('clinical');

            if (config('app.diagnosic_bloodbank') == $tag) {
                $model->quantity = $lab->get('qty');
            }

            if (!$model->save()) {
                throw new EhrException('Failed to save Diagnostic LAB order', 500, [
                    'model' => $model->toArray(),
                    'data' => $data
                ], true);
            }
            $index += 1;
        }

        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
            'message' => 'LAB Diagnostic Orders is Successfully Saved!',
            'batch' => $data,
        ];
    }

    public function saveDiagnosticRadOrders($data)
    {
        $data = collect($data)->recursive();

        // clinical impression to be used will also be initialize from this function

        $this->validateDiagnosticOrder($data ,'rad');

        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();
        $batchOrder = BatchOrderNote::query()->find($batchOrder->id);

        $batchOrder->deleteRadOrder();

        // foreach ($batchOrder->diagnosticOrderRads as $order) {
        //     $key = 'RADIOLOGY:'.$order->service_id;
        //     if (is_null($data->get($key))){
        //         $order->is_deleted = 1;
        //         $order->save();
        //     }
        // }
        
        $service_ids = [];
        foreach ($data as $key => $lab) {
            $service_ids[] = explode(':', $lab->get('servicesID'))[1];
        }
        
        $services = collect(RadiologyService::getRadServices($this->encounter, $service_ids));

        $index = 0;
        foreach ($data as $key =>  $lab) {
            /**
             * @var Collection $lab
             */
                
        $service_id = explode(':', $lab->get('servicesID'))[1];
        $service = collect($services->where('id', $lab->get('servicesID'))->first());

            // $model = DiagnosticOrderRad::query()->find($lab->get('id'));

            // if(is_null($model)){
            //     $model = new DiagnosticOrderRad();
            //     $model->id = Str::uuid();
            //     $model->order_batchid = $batchOrder->id;
            //     $model->order_groupid = 2;
            //     $model->order_dt = date('Y-m-d H:i:s');
            // }

            // if($index == 0){
            //     $stat = $lab->get('stat');
            //     $cash = $lab->get('cash');
            //     $charge = $cash ? !$cash : $lab->get('charge');
            //     $charge_type = $cash ? null : $lab->get('charge_type');
            //     $remarks = $lab->get('remarks');
            // }    
            
            $model = new DiagnosticOrderRad();
            $model->id = Str::uuid();
            $model->order_batchid = $batchOrder->id;
            $model->order_groupid = 2;
            $model->order_dt = date('Y-m-d H:i:s');

            $stat = $lab->get('stat');
            $cash = $lab->get('cash');
            $charge = $cash ? !$cash : $lab->get('charge');
            $charge_type = $cash ? null : $lab->get('charge_type');
            $remarks = $lab->get('remarks');

            $model->encounter_no = $this->encounter->encounter_no;
            $model->doctor_id = auth()->user()->personnel_id;
            $model->service_id = $service_id;
            $model->is_stat = $lab->get('stat');
            $model->is_cash = $lab->get('cash');
            $model->price_cash = $service->get('net_price');
            $model->price_cash_orig = $service->get('cash_price');
            $model->price_charge = $service->get('charge_price');
            $model->transaction_type = $lab->get('charge_type');
            // $model->charge = $charge;
            $model->due_dt = date('YmdHis', $lab->get('requestDate'));    /*Due Date is Order Date in UI*/
            $model->remarks = $remarks;   /*Remarks is Comments in UI*/
            $model->impression = $this->impression_remark;


            if (!$model->save()) {
                throw new EhrException('Failed to save Diagnostic RAD order', 500, [
                    'model' => $model->toArray(),
                    'data' => $data
                ], true);
            }

            $index++;
        }

     
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'RAD Diagnostic Orders is Successfully Saved!',
                'batch'    => $data,
        ];

    }

    public function saveReferralOrders($data)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $data = collect($data);
        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();

        $encounter_date = strtotime($this->encounter->encounter_date);
        $encounter_type = $this->encounter->deptEncounter->deptenc_code;

        $referralDate = strtotime(date('Y-m-d',$data->get('referral_date', date('Y-m-d'))).' '.$data->get('referral_time',''));


        if ($encounter_date > $referralDate) {
            if(strtoupper($encounter_type) == "IPE") {
                throw new EhrException('Referral Date must be greater than Admission Date!', 500);
            } else {
                throw new EhrException('Referral Date must be greater than Consultation Date!', 500);
            }
        } else {
            $refDoctor_id = auth()->user()->personnel_id;

            $dept = PersonnelAssignment::query()->where('personnel_id', $refDoctor_id)->first();

            $model = ReferralOrder::query()->where('id', $data->get('id',null))->first();

            $referral_details = [
                    'referral_type'  => $data->get('referral_type'),
                    'department'     => $data->get('department'),
                    'area'           => $dept->area_id,
                    'reason'         => $data->get('reason'),
                    'date'           => empty($data->get('referral_date')) ? strtotime(date('Y-m-d')) : $data->get('referral_date'),
                    'time'           => empty($data->get('referral_time')) ? strtotime(date('h:i:s')) : $data->get('referral_time'),

                    'institution'                  => $data->get('institution'),
                    'remarks'                      => $data->get('remarks'),
                    'documentation_included'       => $data->get('documentation_included'),
                    'documentation_included_other' => $data->get('documentation_included_other'),
                    'referral_date'                => empty($data->get('referral_date')) ? strtotime(date('Y-m-d')) : $data->get('referral_date'),
                    'referral_date_strtotime'      => empty($data->get('referral_date')) ? date('Y-m-d') : date('Y-m-d', $data->get('referral_date')),
                    'referral_time'                => empty($data->get('referral_time')) ? date('h:i a') : $data->get('referral_time'),
                    'referral_time_ampm'           => empty($data->get('referral_time')) ? date('h:i a') : date('h:i a', strtotime($data->get('referral_time'))),

                    'referral_check' => $data->get('referral_check'),
            ];

            if (is_null($model)) {
                $model = new ReferralOrder();
                $model->id = Str::uuid();
                $model->create_id = auth()->user()->personnel_id;
                $model->create_dt = date('Y-m-d h:i:s');
                $model->order_batchid = $batchOrder->id;
            }

            $model->order_groupid = 5;
            $model->referto_doctorid = $refDoctor_id;
            $model->dept_id = $data->get('department');
            $model->area_id = $dept->area_id;
            $model->data = Json::encode($referral_details);
            $model->modify_id = auth()->user()->personnel_id;

            if (!$model->save()) {
                throw new EhrException('Failed to save Referral Order');
            }

            $batchOrder = BatchOrderNote::query()->find($model->order_batchid);
            $notes = $this->getPlanMNotes();
            $data = $this->getThisBatchOrder($batchOrder, $notes);

            return [
                    'message' => 'Referral Orders is Successfully Saved!',
                    'batch'    => $data,
            ];
        }

    }

    public function saveDischargeOrders($data)
    {
        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();

        $model = DischargeOrder::query()->where('id', $data['id'])->first();

        $discharge_details = [
                'order'      => @$data['order'],
                'medication' => @$data['medication'],
                'department' => @$data['department'],
                'doctor'     => @$data['doctor'],
        ];

        if($model) {
            $model->discharge_order =Json::encode($discharge_details);
            $model->follow_up_dt = $data['dateOPD'];
            $model->er_nod = $data['er_nod'];
            $model->modify_id = auth()->user()->personnel_id;
        } else {
            $model = new DischargeOrder();
            $model->id = Str::uuid();
            $model->order_batchid = $batchOrder->id;
            $model->discharge_order =Json::encode($discharge_details);
            $model->follow_up_dt = $data['dateOPD'];
            $model->er_nod = $data['er_nod'];
            $model->create_id = auth()->user()->personnel_id;
            $model->modify_id = auth()->user()->personnel_id;
            $model->create_dt = date('Y-m-d h:i:s');
        }

        if(!$model->save()) {
            throw new EhrException('Failed to save Discharge Order');
        }

        return [
                'message' => 'Discharge Orders is Successfully Saved!',
                'data'    => $model,
        ];

    }

    public function deleteRepetitiveSession($id)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);


        $model = RepetitiveOrder::query()
                    ->where('id', $id)
                    ->first();

        if(is_null($model))
            throw new EhrException('Order does not exist!');
        elseif($model->batch->encounter_no != $this->encounter->encounter_no)
            throw new EhrException('Order does not exist!');

        $model->history = $model->history."\nDeleted By: ".auth()->user()->personnel_id.' '.date("F j,Y h:i a");
        $model->is_deleted = 1;

        if (!$model->save()) {
            throw new EhrException('Failed to delete Repetitive order');
        }

        return [
            'message' => 'Repetitive Session is Successfully Deleted!',
            'data'    => $model,
        ];

    }

    /**
     * delete action for finalized repetitive orders
     */
    public function deleteRepetitiveCoursewardOrder($encounterCourseWardID)
    {
        $model = EncounterCourseward::query()->where('id', $encounterCourseWardID)->first();

        if ($model) {
            $model->is_deleted = 1;

            if (!$model->save()) {
                throw new EhrException('Failed to delete Repetitive Course ward order');
            }
        }
        else
            throw new EhrException('Repetitive Session order does not exist.');

        $batchOrder = BatchOrderNote::query()->find($model->batchorder_id);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'Repetitive Session is Successfully Deleted!',
                'batch'    => $data,
        ];

    }

    public function deleteDiagnosticLabOrders($data)
    {
        if (!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $model = DiagnosticOrderLab::query()->where('id', $data['id'])->first();
   
        if ($model->orderBatch->is_finalized == 0) {
            $model->is_deleted = 1;

            if (!$model->save()) {
                throw new EhrException('Failed to delete Diagnostic Order');
            }
        } else {
            throw new EhrException('Failed to delete Diagnostic Order');
        }

        return [
            'message' => "Diagnostic Orders is Successfully Deleted!",
            'data' => $model,
        ];

    }

    public function deleteDiagnosticRadOrders($data)
    {
        $model = DiagnosticOrderRad::query()->where('id', $data['id'])->first();

        if ($model) {
            if ($model->orderBatch->is_finalized == 0) $model->is_deleted = 1;

            if (!$model->save()) {
                throw new EhrException('Failed to Delete Diagnostic Order');
            }
        }else {
            throw new EhrException('Failed to delete Diagnostic Order');
        }

        return [
                'message' => 'Diagnostic Orders is Successfully Deleted!',
                'data'    => $model,
        ];

    }

    public function deleteReferralOrders($data)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $model = ReferralOrder::query()->where('id', $data['id'])->first();
        if ($model) {
            $model->is_deleted = 1;
            $ok = $model->save();
            if (!$ok) {
                throw new EhrException('Failed to delete Referral Order');
            }
        }
        else
            throw new EhrException('Referral Order does not exist.');



        $batchOrder = BatchOrderNote::query()->find($model->order_batchid);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'Referral Order is Successfully Deleted!',
                'batch'    => $data,
        ];

    }

    public function deleteDischargeOrders($data)
    {
        $model = DischargeOrder::query()->where('id', $data['id'])->first();
        if ($model) {
            $model->is_deleted = 1;
            $ok = $model->save();
            if (!$ok) {
                throw new EhrException('Failed to delete Discharge Order');
            }
        }

        return [
                'message' => 'Discharge Orders is Successfully Deleted!',
                'data'    => $model,
        ];

    }

    public function saveCoursewardOrder($data)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $data = collect($data);
        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();

        $encounter_date = strtotime($this->encounter->encounter_date);
        $encounter_type = $this->encounter->deptEncounter->deptenc_code;

        $orderDate = $data->get('order_date',null);
        $orderTime = $data->get('order_time',null);

        $orderDate_formated = date('Y-m-d',$orderDate);
        $orderDate = strtotime($orderDate_formated.' '.$orderTime);

        $this->validateDate($orderDate);

        // if ($encounter_date > $orderDate) {
        //     if(strtoupper($encounter_type) == "IPE") {
        //         throw new EhrException('Courseward Date must be greater than Admission Date', 500);
        //     } else {
        //         throw new EhrException('Courseward Date must be greater than Consultation Date', 500);
        //     }
        // }


        $model = CoursewardOrder::query()->where('id', $data->get('id', null))->first();
        $orderAction = $data->get('order',null);
        $orderDate = is_null($data->get('order_date',null)) ? date('Y-m-d') : $orderDate_formated;
        $orderTime = empty($data->get('order_time',null)) ? date('H:i:s') : $data->get('order_time',null);
        $orderCheck = $data->get('order_check',null);

        if (is_null($model)) {
            $model = new CoursewardOrder();
            $model->id = Str::uuid();
            $model->order_dt = date('Y-m-d H:i:s');
            $model->order_batchid = $batchOrder->id;
            $model->create_id = auth()->user()->personnel_id;
            $model->create_dt = date('Y-m-d H:i:s');
        }

        $model->action = $orderAction;
        $model->order_date = $orderDate;
        $model->order_time = $orderTime;
        $model->order_checked = $orderCheck;
        $model->modify_id = auth()->user()->personnel_id;
        $model->modify_dt = date('Y-m-d H:i:s');

        if (!$model->save()) {
            throw new EhrException('Failed to save Courseward Order');
        }


        $batchOrder = BatchOrderNote::query()->find($batchOrder->id);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'Courseward Orders is Successfully Saved!',
                'batch'    => $data,
        ];
    }


    public function deleteCoursewardOrder($id)
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);


        $model = CoursewardOrder::query()->find($id);

        if(is_null($model))
            throw new EhrException('Courseward Order does not exist.');

        $model->deleted_at = date('YmdHis');
        if(!$model->save())
            throw new EhrException('Courseward Order deleteion failed.');


        $batchOrder = BatchOrderNote::query()->find($model->order_batchid);
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);
        return [
                'message' => 'Courseward Orders is Successfully deleted!',
                'batch'    => $data,
        ];

    }



    public function createCourseWard()
    {

        $batchOrder = $this->getCurrentUnfinalizedBatchOrderNote();
        $this->id = $batchOrder->id;
        $this->encounter_no = $batchOrder->encounter_no;
        $this->doctor_id = $batchOrder->doctor_id;
        $this->order_date = $batchOrder->create_dt;

        $coursewards = CoursewardOrder::query()
            ->where('order_batchid', $batchOrder->id)
            ->whereNull('deleted_at')
            ->get();
        if (count($coursewards) > 0) {
            foreach ($coursewards as $courseward) {
                $model = new EncounterCourseward();
                $model->id = Str::uuid();
                $model->batchorder_id = $this->id;
                $model->order_date = $courseward->order_date . ' ' . $courseward->order_time;
                $model->encounter_no = $batchOrder->encounter_no;
                $model->doctor_id = $this->doctor_id;
                $model->action = $courseward->action;

                if (!$model->save()) {
                    throw new EhrException('Courseward order not saved!');
                }
            }
        }


        if ($this->checkMedsOrder()) {
            $this->saveCourseWardMedsOrder();
        }

        $this->checkDiagOrder();

        if ($this->checkReferralOrder()) {
            $this->saveCourseWardReferralOrder();
        }

        if ($this->checkDischargeOrder()) {
            $this->saveCourseWardDischargeOrder();
        }
    }

    public function checkDiagOrder()
    {
        $this->checkLabOrder();
        $this->checkRadOrder();
        $this->checkAuxOrder();
        $this->checkPackageOrder();
        $this->checkRepetitiveOrder();
    }

    public function checkRepetitiveOrder()
    {
        $repOrders = $this->getCurrentUnfinalizedBatchOrderNote()->repetitiveSessionOrders()->get();
        if (count($repOrders) > 0) {
            foreach ($repOrders as $order) {
                $model = new EncounterCourseward();
                $model->id = Str::uuid();
                $model->batchorder_id = $this->id;
                $model->order_date = $order->session_start_date . ' ' .$order->session_start_time;
                $model->encounter_no = $this->encounter_no;
                $model->doctor_id = $this->doctor_id;

                $rep = $order->diagnosisProcedure;
                $st = date('Y-m-d', strtotime($order->session_start_date));
                $en = date('Y-m-d', strtotime($order->session_end_date));

                $s = new \DateTime($st);
                $e = new \DateTime($en);

                $dateDiff = $s->diff($e);
                $dDiff = $dateDiff->format('%r%a');

                if ($dDiff > 0) {
                    $key = $rep->procedure.' SESSION, '.$order->remarks.' ('.date('h:i a', strtotime($order->session_start_time)).' to '.date('F j, Y', strtotime($order->session_end_date)).' '.date('h:i a', strtotime($order->session_end_time)).').';
                } else {
                    $key = $rep->procedure.' SESSION, '.$order->remarks.' ('.date('h:i a', strtotime($order->session_start_time)).' to '.date('h:i a', strtotime($order->session_end_time)).').';
                }

                $model->action = $key;

                if (!$model->save()) {
                    throw new EhrException('Failed to save repetitive session order');
                }
            }
        }
    }


    public function checkPackageOrder()
    {
        $packageOrders = $this->getCurrentUnfinalizedBatchOrderNote()->servicePackageOrders()->get();
        if (count($packageOrders) > 0) {
            $key = 'PACKAGE ORDER: ';
            $modelInsert = new EncounterCourseward();
            $modelInsert->id = Str::uuid();
            $modelInsert->batchorder_id = $this->id;
            $modelInsert->order_date = $this->order_date;
            $modelInsert->encounter_no = $this->encounter_no;
            $modelInsert->doctor_id = $this->doctor_id;
            foreach ($packageOrders as $order) {
                $key .= $order->packageCode->package_name . ", ";
            }
            $key = rtrim($key, ", ");
            $key .= ".";
            $modelInsert->action = $key;
            $ok = $modelInsert->save();
        }
    }

    public function checkAuxOrder()
    {
        $auxOrders = $this->getCurrentUnfinalizedBatchOrderNote()->auxiliaryServiceOrders()->get();
        if (count($auxOrders) > 0) {
            $key = 'AUXILIARY ORDER:';
            $model = new EncounterCourseward();
            $model->id = Str::uuid();
            $model->batchorder_id = $this->id;
            $model->order_date = $this->order_date;
            $model->encounter_no = $this->encounter_no;
            $model->doctor_id = $this->doctor_id;
            foreach ($auxOrders as $order) {
                $key .= $order->service->service_name . ", ";
            }
            $key = rtrim($key, ", ");
            $key .= ".";
            $model->action = $key;
            $ok = $model->save();
        }
    }

    public function checkRadOrder()
    {
        $radOrders = $this->getCurrentUnfinalizedBatchOrderNote()->diagnosticOrderRads()->get();
        if (count($radOrders) > 0) {
            $key = 'RADIOLOGY ORDER: ';
            $model = new EncounterCourseward();
            $model->id = Str::uuid();
            $model->batchorder_id = $this->id;
            $model->order_date = $this->order_date;
            $model->encounter_no = $this->encounter_no;
            $model->doctor_id = $this->doctor_id;
            foreach ($radOrders as $order) {
                $key .= $order->service->service_name . ", ";
            }
            $key = rtrim($key, ", ");
            $key .= ".";
            $model->action = $key;
            $ok = $model->save();
        }
    }

    public function checkLabOrder()
    {
        $labOrders = $this->getCurrentUnfinalizedBatchOrderNote()->diagnosticOrderLabs()->get();

        if (count($labOrders) > 0) {
            $key = 'LABORATORY ORDER: ';
            $model = new EncounterCourseward();
            $model->id = Str::uuid();
            $model->batchorder_id = $this->id;
            $model->order_date = $this->order_date;
            $model->encounter_no = $this->encounter_no;
            $model->doctor_id = $this->doctor_id;
            foreach ($labOrders as $order) {
                $key .= $order->service->service_name . ", ";
            }
            $key = rtrim($key, ", ");
            $key .= ".";
            $model->action = $key;
            $ok = $model->save();
        }
    }

    public function checkDischargeOrder()
    {
        $model = $this->getCurrentUnfinalizedBatchOrderNote()->dischargeOrders()->get();
        if (count($model) > 0) {
            return true;
        }

        return false;
    }


    public function saveCourseWardDischargeOrder()
    {
        $model = $this->getCurrentUnfinalizedBatchOrderNote()->dischargeOrders()->get();

        foreach ($model as $disOrder) {
            $model = new EncounterCourseward();
            $model->id = Str::uuid();
            $model->batchorder_id = $this->id;
            $model->order_date = $this->order_date;
            $model->encounter_no = $this->encounter_no;
            $model->doctor_id = $this->doctor_id;
            $model->action = $key = 'DISCHARGE ORDER';
            $ok = $model->save();
        }
    }


    public function checkReferralOrder()
    {
        $model = $this->getCurrentUnfinalizedBatchOrderNote()->referralOrders()->get();

        if (count($model) > 0) {
            return true;
        }

        return false;
    }

    public function saveCourseWardReferralOrder()
    {

        $model = $this->getCurrentUnfinalizedBatchOrderNote()->referralOrders()->get();

        if (count($model) > 0) {
            $data = json_decode($model[0]->data, true);

            foreach ($model as $refOrder) {
                $model = new EncounterCourseward();
                $model->id = Str::uuid();
                $model->batchorder_id = $this->id;
                $model->order_date = $data['referral_date_strtotime'] . ' ' . $data['referral_time'];
                $model->encounter_no = $this->encounter_no;
                $model->doctor_id = $this->doctor_id;

                $department = DepartmentsCatalog::query()->find($refOrder->dept_id);
                if ($data['reason'] != null or $data['reason'] != "") {
                    $reason = ReferralPurposeCatalog::query()->find($data['reason']);
                    $model->action = $key = 'REFER TO: ' . $department->dept_name . ', REFERRAL REASON: ' . $reason->purpose_desc;
                } else {
                    $reason = "";
                    $model->action = $key = 'REFER TO: ' . $department->dept_name . ', REFERRAL REASON: ' . $reason;
                }
                $ok = $model->save();
            }
        }
    }


    public function checkMedsOrder()
    {
        $model = $this->getCurrentUnfinalizedBatchOrderNote()->medsOrders()->get();

        if (count($model) > 0) {
            return true;
        }

        return false;
    }


    public function saveCourseWardMedsOrder()
    {
        $model = $this->getCurrentUnfinalizedBatchOrderNote()->medsOrders()->get();

        if (count($model) > 0) {
            $key = 'PRESCRIPTION ORDER:';
            $modelInsert = new EncounterCourseward();
            $modelInsert->id = Str::uuid();
            $modelInsert->batchorder_id = $this->id;
            $modelInsert->order_date = $model[0]->order_date . ' ' . $model[0]->order_time;
            $modelInsert->encounter_no = $this->encounter_no;
            $modelInsert->doctor_id = $this->doctor_id;
            foreach ($model as $medOrder) {
                if ($medOrder->is_custom != 1) {
                    $item = PhilMedicine::query()->find($medOrder->drug_code);
                    if ($item) {
                        $key .= $item->description . ", ";
                    }
                } else {
                    $key .= $medOrder->custom_item . ", ";
                }
            }
            $key = rtrim($key, ", ");
            $key .= ".";
            $modelInsert->action = $key;
            $ok = $modelInsert->save();
        }
    }



    public function finalizeOrders()
    {
        if(!$this->permService->hasPlanManEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $note = $this->getUnfinalizedBatchOrderNote();

        if(is_null($note))
            throw new EhrException('No unfinalized orders.');

        $this->createCourseWard();

        $note->is_finalized = true;
        if(!$note->save())
            throw new EhrException('Batch orders note not finalized.');

        $batchOrder = $note;
        $notes = $this->getPlanMNotes();
        $data = $this->getThisBatchOrder($batchOrder, $notes);

        $referral = $this->finalizeReferral($data);

        return [
                'msg' => 'Orders finalized.',
                'batch'    => $data,
                'referral' => $referral
        ];
    }

    public function finalizeReferral($values)
    {
        $encDetails = Encounter::query()->where('encounter_no', $this->encounter_no)->first();
        $exist = HisReferral::query()->where('encounter_nr', $this->encounter_no)->orderByDesc('referral_nr')->first();
        $ok = false;
        foreach (collect($values) as $key => $value) {
            foreach ($value['orders'] as $data) {
                if($data['kardexGroup'] === 'Referral') {
                    $referral = new HisReferral();
                    $referral->referral_nr = $exist['referral_nr'] ? $exist['referral_nr'] + 1 : $encDetails->spin.'1';
                    $referral->encounter_nr = $this->encounter_no;
                    $referral->referrer_dr = $data['referto_doctorid'];
                    $referral->referrer_dept = $data['department'];
                    $referral->is_referral = 1;
                    $referral->is_dept = 1;
                    $referral->reason_referral_nr = $data['reason'];
                    $referral->history = 'Create By: '.$data['create_id'].' ['.$data['referral_date_strtotime']. ' '.$data['referral_time_ampm'].'] FROM Mobile EHR';
                    $referral->create_id = $data['create_id'];
                    $referral->create_time = $data['create_dt'];
                    $referral->referral_date = $data['referral_date_strtotime'];

                    $ok = $referral->save();
                    if(!$ok){
                        throw new EhrException('Failed to save referral.', 500);
                    }
                }
            }
        }

        if ($ok) {
            return true;
        } else {
            return false;
        }
    }
}
