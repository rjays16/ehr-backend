<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/6/2019
 * Time: 1:05 PM
 */

namespace App\Services\Nurse;


use App\Models\BatchOrderNote;
use App\Models\CoursewardOrder;
use App\Models\DiagnosticOrderLab;
use App\Models\DischargeOrder;
use App\Models\Encounter;
use App\Exceptions\EhrException\EhrException;
use App\Models\DiagnosticOrderRad;
use App\Models\KardexMedication;
use App\Models\KardexRadiology;
use App\Models\LabService;
use App\Models\MedsOrder;
use App\Models\Mongo\Tracer\TracerActivity;
use App\Models\NurseNotes;
use App\Models\NurseNotesBatch;
use App\Models\NurseWardCatalog;
use App\Models\Order\Diagnostic\LaboratoryService;
use App\Models\PersonCatalog;
use App\Models\PersonnelCatalog;
use App\Models\PhilMedicine;
use App\Models\RadioService;
use App\Models\ReferralOrder;
use App\Models\RepetitiveOrder;
use App\Models\TracerNurseNotes;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Personnel\PersonnelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use App\Models\KardexLaboratory;
use App\Models\TransactionType;
use Illuminate\Support\Collection;

class NurseService
{
    public $encounter;

    public $personnel;

    const IS_FINALIZED = 1;

    private $tracesActivity = [];

    public $tracerAssesssment;

    private $tracer;

    public function __construct(Encounter $encounter = null)
    {
        $user = auth()->user();
        $this->personnel = $user->personnel_id;
        $this->encounter = $encounter;
    }

    public static function init($encounter)
    {
        $encounter = Encounter::query()->where("encounter_no", $encounter)->first();
        if (!$encounter)
            throw new EhrException('Encounter does not exist.');

        return new NurseService($encounter);
    }

    public function getBatchOrders($options = [])
    {
        return BatchOrderNote::query()
            ->where(
                [
                    ['encounter_no', '=', $this->encounter->encounter_no],
                ]
            )->where(
                function ($e) {
                    $e->where('is_finalized', 1);
                }
            )->orderByDesc('create_dt')->get();
    }

    public function getAllOrders()
    {
        $carryouts = $this->getBatchOrders();

        $data = [
            'prescription' => [],
            'laboratory' => [],
            'radiology' => [],
        ];

        foreach ($carryouts as $key => $carryout) {
            /**
             * @var BatchOrderNote $carryout
             */
            $pharmacyCarry = MedsOrder::query()->where('order_batchid', $carryout->id)->first();
            $laboratoryCarry = DiagnosticOrderLab::query()->where('order_batchid', $carryout->id)->first();
            $radiologyCarry = DiagnosticOrderRad::query()->where('order_batchid', $carryout->id)->first();

            if ($pharmacyCarry || $laboratoryCarry || $radiologyCarry) {
                $row = [
                    'batch_id'        => $carryout->id,
                    'finalized'       => $carryout->is_finalized,
                    'doctorId'        => $carryout->doctor_id,
                    'doctor'          => $carryout->doctor ? $carryout->doctor->getFullNameWithHonorific() : '',
                ];

                // $orders = [];

                $data = $this->carryLaboratory($carryout, $data, $row);

                $data = $this->carryRadiology($carryout, $data, $row);

                $data = $this->carryPharmacy($carryout, $data, $row);

            }
        }

        return collect($data);
    }

    public function carryLaboratory(BatchOrderNote $carryouts, $orders = [], $batch_data = [])
    {
        foreach ($carryouts->diagnosticOrderLabs as $lab) {
            /**
             * @var DiagnosticOrderLab $lab
             */
            $kardexLabs = KardexLaboratory::query()->where('diagnosticorder_id', $lab->id)->first();
            $is_carry = 0;
            $status = 'FINALIZED';
            $status_order = 'PENDING';
            if($kardexLabs) {
                $status = 'VERIFIED';
                $is_carry = 1;
            }

            if($carryouts->is_finalized){
                $status_order = $lab->is_served ? 'Served' : 'Not Yet Served';
            }

            $labService = LabService::query()->find($lab->service_id);
            $item = (new LaboratoryService($labService))->toArray();
            $item_belong = [$labService->group_id];

            $service_special = [
                'SPL', /*SPECIAL LABORATORY*/
                'SPC', /*CARDIO PULMONARY SERVICES*/
                'ECHO', /*2DECHO*/
                'CATH' /*CV CATHETERIZATION LABORATORY*/
            ];

            $service_blood = [
                'B', /*BLOOD BANK*/
            ];

            if (array_intersect($item_belong, $service_blood)) {
                $service_name = config('diagnosic_bloodbank');
            } elseif(array_intersect($item_belong, $service_special)) {
                $service_name = config('diagnosic_special_laboratory');
            } else {
                $service_name = config('diagnosic_laboratory');
            }

            $charge_type_desc = '';
            if(!($lab->is_cash)){
                $charge_type_desc = TransactionType::getChargeType($lab->charge_type);
            }
            

            @$orders['laboratory'][] = [
                'id'            => $lab->id,
                'carry_id'      => $kardexLabs->id ? $kardexLabs->id : '',
                'ref_no'        => $lab->refno,
                'encounterNo'   => $lab->encounter_no,
                'item'          => [
                    'name'          => $item['name'],
                    'price_cash'    => $item['price_cash'],
                    'price_charge'  => $item['price_charge'],
                    'is_socialized' => $item['is_socialized']
                ],
                'service_name'  => $service_name,
                'nurse'         => $kardexLabs->nurse ? $kardexLabs->nurse->getFullNameWithHonorific() : '',
                'cash'          => $lab->is_cash ? true : false,
                'charge_type'   => $lab->charge_type,
                'charge_type_desc'   => $charge_type_desc,
                'stat'          => $lab->is_stat ? true : false,
                'impression'    => $lab->impression ,
                'comments'      => $lab->remarks,
                'order_date'    => $lab->order_dt ? strtotime($lab->order_dt) : '',
                'carryout_dt'   => $kardexLabs->carryout_dt ? strtotime($kardexLabs->carryout_dt) : '',
                'status'        => $status,
                'status_order'  => $status_order,
                'is_carry'      => $is_carry ? true : false,
            ]+$batch_data;
        }

        return @$orders;
    }

    

    public function carryRadiology(BatchOrderNote $carryouts, $orders = [], $batch_data = [])
    {
        

        foreach ($carryouts->diagnosticOrderRads as $rad) {
            /**
             * @var DiagnosticOrderRad $rad
             */
            $kardexRads = KardexRadiology::query()->where('diagnosticorder_id', $rad->id)->first();
            $is_carry = 0;
            $status = 'FINALIZED';
            $status_order = 'PENDING';
            if ($kardexRads) {
                $status = 'VERIFIED';
                $is_carry = 1;
            }

            if ($carryouts->is_finalized) {
                $status_order = $rad->is_served ? 'Served' : 'Not Yet Served';
            }

            $radService = RadioService::query()->find($rad->service_id);
            $item = (new LaboratoryService($radService))->toArray();

            $charge_type_desc = '';
            if(!($rad->is_cash)){
                $charge_type_desc = TransactionType::getChargeType($rad->charge_type);
            }

            @$orders['radiology'][] = [
                'id'            => $rad->id,
                'carry_id'      => $kardexRads->id ? $kardexRads->id : '',
                'ref_no'        => $rad->refno,
                'encounterNo'   => $rad->encounter_no,
                'item'          => [
                    'name'          => $item['name'],
                    'price_cash'    => $item['price_cash'],
                    'price_charge'  => $item['price_charge'],
                    'is_socialized' => $item['is_socialized']
                ],
                'service_name'  => config('app.diagnosic_radiology'),
                'nurse'         => $kardexRads->nurse ? $kardexRads->nurse->getFullNameWithHonorific() : '',
                'cash'          => $rad->is_cash ? true : false,
                'charge_type'   => $rad->charge_type,
                'charge_type_desc'   => $charge_type_desc,
                'stat'          => $rad->is_stat ? true : false,
                'impression'    => $rad->impression ,
                'comments'      => $rad->remarks,
                'order_date'    => $rad->order_dt ? strtotime($rad->order_dt) : '',
                'carryout_dt'   => $kardexRads->carryout_dt ? strtotime($kardexRads->carryout_dt) : '',
                'status'        => $status,
                'status_order'  => $status_order,
                'is_carry'      => $is_carry ? true : false,
            ]+$batch_data;
        }

        return @$orders;
    }

    public function carryPharmacy(BatchOrderNote $carryouts, $orders = [], $batch_data = [])
    {
        foreach ($carryouts->medsOrders as $med) {
            /**
             * @var MedsOrder $med
             */
            $kardexMeds = KardexMedication::query()->where('docorder_id', $med->id)->first();
            $is_carry = 0;
            $status = 'FINALIZED';
            $status_order = 'PENDING';
            if ($kardexMeds) {
                $status = 'VERIFIED';
                $is_carry = 1;
            }

            if ($carryouts->is_finalized) {
                $status_order = $med->is_served ? 'Served' : 'Not Yet Served';
            }

            $medicines = PhilMedicine::query()->where('drug_code', $med->drug_code)->first();
            
            @$orders['prescription'][] = [
                'id'            => $med->id,
                'carry_id'      => $kardexMeds->id ? $kardexMeds->id : '',
                'ref_no'        => $med->refno,
                'encounterNo'   => $med->encounter_no,
                'item'          => ($medicines ? $medicines->description : $med->item->item_name) ?: $med->custom_item,
                'sig'           => $med->sig,
                'qty'           => $med->quantity,
                'service_name'  => $med->is_ehr ? 'Prescription' : 'Pharmacologic/Medication',
                'nurse'         => $kardexMeds->nurse ? $kardexMeds->nurse->getFullNameWithHonorific() : '',
                // 'cash'          => $med->is_cash ? true : false,
                // 'stat'          => $med->is_stat ? true : false,
                'remarks'       => $med->remarks,
                'order_date'    => $med->order_date ? strtotime($med->order_date.' '.$med->order_time) : '',
                'carryout_dt'   => $kardexMeds->carryout_dt ? strtotime($kardexMeds->carryout_dt) : '',
                'status'        => $status,
                'status_order'  => $status_order,
                'is_carry'      => $is_carry ? true : false,
            ]+$batch_data;
        }

        return @$orders;
    }

    public function getPatientLists($data)
    {

        $params = [];
        $person_search = $data['person_search'];
        $ward_search = $data['ward_search'];
        $pdo = DB::getPDO();

        $is_encounter = "";
        $is_spin = "";
        $is_name = "";
        $is_ward = "";
        $search = explode(",", $person_search);
        $default_con = " sde.`is_deleted` <> 1 AND se.`is_cancel` <> 1";
        if ($person_search != "") {
            if (strlen($person_search) >= 10 && is_numeric($person_search)) {
                $params['person_search_encounter'] = $person_search;
                $is_encounter = " AND (se.encounter_no = :person_search_encounter)";
            } else {
                if (is_numeric($person_search)) {
                    $params['person_search_spin'] = $person_search;
                    $is_spin = " AND (se.spin = :person_search_spin)";
                } else {
                    $params['l_name'] = trim($search[0])."%";
                    $params['l_names'] = trim($search[0])."%";
                    $is_name = " AND (spa.name_last LIKE :l_name OR spa.name_first LIKE :l_names)";
                    if (!empty($search[1])) {
                        $params['f_name'] = trim($search[1])."%";
                        $params['f_names'] = trim($search[1])."%";
                        $is_name .= " AND (spa.name_first LIKE :f_name OR spa.name_last LIKE :f_names)";
                    }
                }
            }
        }

        if ($ward_search != "") {
            $params['ward'] = $ward_search;
            $is_ward .= " AND snw.nr = :ward";
        }

        $stm = $pdo->prepare("SELECT DISTINCT 
                  se.encounter_no,
                  spa.pid,
                  se.encounter_date,
                  spa.name_first,
                  spa.name_last,
                  spa.name_middle,
                  spa.suffix,
                  spa.gender,
                  spa.birth_date,
                  se.admit_diagnosis2,
                  se.discharge_dt,
                  se.discharge_id,
                  se.is_discharged,
                  se.parent_encounter_nr,
                  sed.doctor_id,
                  sed.role_id,
                  sde.deptenc_code,
                  sde.er_areaid,
                  sac.area_id,
                  sac.area_code,
                  sac.area_desc,
                  snw.ward_id,
                  snw.name,
                  snw.description,
                  snr.info as room_info
                FROM
                  (SELECT 
                    se0.* 
                  FROM
                    smed_encounter se0 
                  WHERE (
                      se0.is_cancel IS NULL 
                      OR se0.is_cancel = 0
                    ) 
                    {$is_encounter} {$is_spin}
                ORDER BY se0.encounter_date DESC) se 
                INNER JOIN smed_person_catalog spa 
                  ON se.spin = spa.pid 
                LEFT JOIN smed_batch_order_note sbon 
                  ON sbon.encounter_no = se.encounter_no 
                LEFT JOIN smed_referral_order sro 
                  ON sro.order_batchid = sbon.id 
                LEFT JOIN smed_encounter_doctor sed 
                  ON sed.encounter_no = se.encounter_no 
                LEFT JOIN smed_dept_encounter sde 
                  ON sde.encounter_no = se.encounter_no 
                LEFT JOIN smed_area_catalog sac 
                  ON sde.er_areaid = sac.area_id 
                LEFT JOIN smed_nurse_ward_catalog snw
                    ON snw.`nr` = sde.current_ward_nr
                LEFT JOIN `smed_nurse_room_catalog` snr
                    ON snr.nr = sde.`room_no`
                    AND snr.`ward_nr` = sde.`current_ward_nr`
                WHERE (
                  sed.is_deleted IS NULL 
                  OR sed.is_deleted = 0
                ) {$is_name} {$is_ward}
                LIMIT 20");

        $stm->execute($params);
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    public static function getWards()
    {
        $model = new NurseWardCatalog();
        $data = $model->getWards();
        return $data;
    }

    public static function getNoteOptions()
    {
        return [
            "data",
            "action",
            "response"
        ];
    }

    public function actionNurseBatch($data)
    {
        $notesBatchModel = NurseNotesBatch::query()
            ->where([
                ["is_finalized", "!=", 1],
                ["encounter_no", $this->encounter->encounter_no]
            ])
            ->first();

        if (empty($notesBatchModel)) {
            $notesBatchModel = new NurseNotesBatch();
            $notesBatchModel->id = (string)Str::uuid();
            $notesBatchModel->encounter_no = $this->encounter->encounter_no;
            $notesBatchModel->is_finalized = 0;
            $notesBatchModel->nurse_id = $this->personnel;
            $notesBatchModel->create_dt = date('Y-m-d H:i:s');
        }
        $notesBatchModel->modify_dt = date('Y-m-d H:i:s');
        if (!$notesBatchModel->save())
            throw new EhrException('Unable to save nurse batch note');

        //Mongo
        $nurseNotesMongoModel = TracerNurseNotes::query()->find($data['document_id']);

        $type = 'old';
        if (empty($nurseNotesMongoModel)) {
            $nurseNotesMongoModel = new TracerNurseNotes();
            $nurseNotesMongoModel->encounter_no = $this->encounter->encounter_no;
            $nurseNotesMongoModel->focus = $data['focus'];
            $nurseNotesMongoModel->data = $data['data'];
            $nurseNotesMongoModel->action = $data['action'];
            $nurseNotesMongoModel->response = $data['response'];
            $nurseNotesMongoModel->create_dt = date('Y-m-d H:i:s');
            $type = 'new';
        }
        $nurseNotesMongoModel->modify_dt = date('Y-m-d H:i:s');
        $nurseNotesMongoModel->focus = $data['focus'];
        $nurseNotesMongoModel->data = $data['data'];
        $nurseNotesMongoModel->action = $data['action'];
        $nurseNotesMongoModel->response = $data['response'];
        if(!$nurseNotesMongoModel->save())
            throw new EhrException('Unable to save mongo note');

        $this->tracesActivity[] = new TracerActivity($type, $nurseNotesMongoModel);

        //nurse notes
        $nurseNotesModel = "";
        if ($type == "new") {
            $nurseNotesModel = new NurseNotes();
            $nurseNotesModel->id = (string)Str::uuid();
            $nurseNotesModel->notes_batchid = $notesBatchModel['id'];
            $nurseNotesModel->document_id = $nurseNotesMongoModel['_id'];
            $nurseNotesModel->nurse_id = $this->personnel;
            $nurseNotesModel->create_dt = date('Y-m-d H:i:s');
            if (!$nurseNotesModel->save())
                throw new EhrException('Unable to save nurse note');
        } else {
            $nurseNotesModel = NurseNotes::query()
                ->where("document_id", $nurseNotesMongoModel->_id)
                ->first();
        }
        $nurseListsOfNotes = $this->getBatchNote();


        return [
            'message' => "Nurse notes successfully saved!",
            'data' => [
                "notesBatchModel" => $notesBatchModel,
                "nurseNotesMongoModel" => $nurseNotesMongoModel,
                "nurseNotesModel"   => $nurseNotesModel,
                "nurseListsOfNotes" => $nurseListsOfNotes,
            ]
        ];
    }

    public function resetTracerUpdate()
    {
        /**
         * @var TracerActivity $tracer
         * @var Collection $tracer->tracer
         * @var string $tracer->type
         */

        foreach ($this->tracesActivity as $key => $tracer) {
            $id = $tracer->tracer->get('_id');

            $ass = TracerNurseNotes::query()->find($id);

            if($tracer->type == 'new')
                $ass->forceDelete();
            else{
                $tracer->tracer = $tracer->tracer->forget('_id');
                $ass->update($tracer->tracer->toArray());
            }
        }
    }

    public function deleteDarNote($data)
    {
        $nurseNote = NurseNotes::query()
            ->where("id", $data['id'])
            ->first();

        if (empty($nurseNote))
            throw new EhrException('No ID found ' . $data['id']);

        $nurseNote->flag_document_id = 1;

        if (!$nurseNote->save())
            throw new EhrException('Unable to flag-out the nurse note');

        $nurseNotesMongoModel = TracerNurseNotes::query()->find($nurseNote->document_id);

        if (!$nurseNotesMongoModel->delete())
            throw new EhrException('Unable to delete nurse note');

        return [
            'message' => 'Nurse notes deleted successfully',
            'data' => $nurseNotesMongoModel
        ];
    }

    public function getBatchNote(){
        $nurseBatchNote = NurseNotesBatch::query()
            ->where("encounter_no", $this->encounter->encounter_no)
            ->with("personnel.p", "notes.darnotes", "notes.personnel.p")
            ->get();
        foreach ($nurseBatchNote as $key => $batchinfo){
            $firstNameBatch = $batchinfo->personnel->p->name_last;
            $lastNameBatch = $batchinfo->personnel->p->name_first;
            foreach ($batchinfo->notes as $key1 => $notesinfo){
                $firstNameNote = $notesinfo->personnel->p->name_first;
                $lastNameBatch = $notesinfo->personnel->p->name_last;
                $notesinfo['nurse_note_name'] = $firstNameNote.' '.$lastNameBatch;
            }
            $batchinfo['nurse_batch_name'] = $firstNameBatch.' '.$lastNameBatch;
        }
        return $nurseBatchNote;
    }

    public function getDarNotes($data)
    {
        $nurseNotesMongoModel = TracerNurseNotes::query()
            ->where("encounter_no", $this->encounter->encounter_no)
            ->get();

        $darnotes = [];
        foreach ($nurseNotesMongoModel as $key => $value) {
            $darnotes [] = [
                "_id" => $value['_id'],
                "encounter_no" => $value['encounter_no'],
                "focus" => $value['focus'],
                "data" => $value['data'],
                "action" => $value['action'],
                "response" => $value['response'],
                "create_dt" => $value['create_dt'],
                "modify_dt" => $value['modify_dt'],
                "updated_at" => $value['updated_at'],
                "created_at" => $value['created_at'],
            ];
        }
    }

    public function finalizeNote($data)
    {
        $noteBatchModel = NurseNotesBatch::query()
            ->where("id", $data['id'])
            ->first();

        if (empty($noteBatchModel))
            throw new EhrException('No batch ID found on ID: ' . $data['id']);

        $noteBatchModel->is_finalized = 1;

        if (!$noteBatchModel->save())
            throw new EhrException('Unable to to set to finalize ID: ' . $data['id']);

        return [
            'message' => 'Finalized successfully!',
            'data' => $noteBatchModel
        ];
    }

    public static function config()
    {
        return [

            'm-patient-wards-lists' => [
                'p-wards' => [
                    'role_name' => [
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'nurse-wards' => [
                        'wards' => [],
                        // 'wards' => self::getWards(),
                    ],
                    'note-options' => [
                        'noteoptions' => [],
                        // 'noteoptions' => self::getNoteOptions(),
                    ],
                ]
            ],
        ];
    }
}
