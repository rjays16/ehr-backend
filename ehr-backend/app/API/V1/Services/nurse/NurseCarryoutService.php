<?php


namespace App\API\V1\Services;


use App\API\V1\Models\laboratory\LaboratoryDetails;
use App\API\V1\Models\laboratory\LaboratoryHeader;
use App\API\V1\Models\pharmacy\KardexMedication;
use App\API\V1\Models\pharmacy\PharmaDetails;
use App\API\V1\Models\pharmacy\PharmaHeader;
use App\API\V1\Models\radiology\RadorderD;
use App\API\V1\Models\radiology\RadorderH;
use App\API\V1\Services\orders\OrderService;
use App\Models\BatchOrderNote;
use App\Models\DiagnosticOrderLab;
use App\Models\DiagnosticOrderRad;
use App\Models\KardexLaboratory;
use App\Models\KardexRadiology;
use App\Models\MedsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class NurseCarryoutService
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function carryOutOrder(Request $request)
    {
        $data = $request->data;

        foreach ($data as $datum) {
            $laboratory = DiagnosticOrderLab::find($datum);
            if ($laboratory) {
                $lab[] = $laboratory;
            }

            $radiology = DiagnosticOrderRad::find($datum);
            if ($radiology) {
                $rad[] = $radiology;
            }

            $medication = MedsOrder::find($datum);
            if ($medication) {
                $meds[] = $medication;
            }
        }

        $this->carryoutLaboratory($lab);

        $this->carryoutRadiology($rad);

        $this->carryoutMedication($meds);
    }


    /**
     * @param  array  $data
     *
     * @throws \Exception
     */
    protected function carryoutLaboratory(array $data): void
    {
        foreach ($data as $datum) {
            /*@var $datum DiagnosticOrderLab*/
            $service = new OrderService();
            /*@var $batch BatchOrderNote*/
            $batch = $datum->orderBatch;
            $referenceNo = $service->generateRefNo($datum);

            if (empty($referenceNo)) {

                $header = new LaboratoryHeader();
                $referenceNo = $service->generateSysId($header);
                $header->refno = $referenceNo;
                $header->encounter_no = $batch->encounter_no;
                $header->spin = $batch->encounterNo->spin;
                $header->create_id = $this->user->personnel_id;
                $header->modify_dt = date('Y-m-d H:i:s');
                $header->create_dt = date('Y-m-d H:i:s');
                $header->modify_id = $this->user->personnel_id;

                if ( ! $header->save()) {
                    throw new Exception('Unable To save Laboratory Header');
                }
            }

            $detail = new LaboratoryDetails();
            $detail->id = Str::uuid();
            $detail->refno = $referenceNo;
            $detail->service_id = $datum->service_id;
            $detail->quantity = $datum->quantity;
            $header->create_id = $this->user->personnel_id;
            $header->modify_dt = date('Y-m-d H:i:s');
            $header->create_dt = date('Y-m-d H:i:s');
            $header->modify_id = $this->user->personnel_id;

            if ( ! $detail->save()) {
                throw new Exception('Unable To save Laboratory detail');
            }

            $kardex = new KardexLaboratory();
            $kardex->id = Str::uuid();
            $kardex->diagnosticorder_id = $datum->id;
            $kardex->service_id = $datum->service_id;
            $kardex->carryout_dt = date('Y-m-d H:i:s');
            $kardex->refno = $referenceNo;
            $kardex->nurse_id = $this->user->personnel_id;

            if ( ! $kardex->save()) {
                throw new Exception('Unable To save Kardex Laboratory');

            }
        }
    }

    /**
     * @param  array  $data
     *
     * @throws \Exception
     */
    protected function carryoutRadiology(array $data): void
    {
        foreach ($data as $datum) {
            /*@var $datum DiagnosticOrderLab*/
            $service = new OrderService();
            /*@var $batch BatchOrderNote*/
            $batch = $datum->orderBatch;
            $referenceNo = $service->generateRefNo($datum);

            if (empty($referenceNo)) {

                $header = new RadorderH();
                $referenceNo = $service->generateSysId($header);
                $header->refno = $referenceNo;
                $header->encounter_no = $batch->encounter_no;
                $header->spin = $batch->encounterNo->spin;
                $header->create_id = $this->user->personnel_id;
                $header->modify_dt = date('Y-m-d H:i:s');
                $header->create_dt = date('Y-m-d H:i:s');
                $header->modify_id = $this->user->personnel_id;

                if ( ! $header->save()) {
                    throw new Exception('Unable To save Radiology Header');
                }
            }

            $detail = new RadorderD();
            $detail->id = Str::uuid();
            $detail->refno = $referenceNo;
            $detail->service_id = $datum->service_id;
            $detail->quantity = $datum->quantity;
            $header->create_id = $this->user->personnel_id;
            $header->modify_dt = date('Y-m-d H:i:s');
            $header->create_dt = date('Y-m-d H:i:s');
            $header->modify_id = $this->user->personnel_id;

            if ( ! $detail->save()) {
                throw new Exception('Unable To save Radiology detail');
            }

            $kardex = new KardexRadiology();
            $kardex->id = Str::uuid();
            $kardex->diagnosticorder_id = $datum->id;
            $kardex->quantity = $datum->quantity;
            $kardex->service_id = $datum->service_id;
            $kardex->carryout_dt = date('Y-m-d H:i:s');
            $kardex->refno = $referenceNo;
            $kardex->nurse_id = $this->user->personnel_id;

            if ( ! $kardex->save()) {
                throw new Exception('Unable To save Kardex Radiology');
            }
        }
    }

    /**
     * @param  array  $data
     *
     * @throws \Exception
     */
    protected function carryoutMedication(array $data)
    {
        foreach ($data as $datum) {
            /*@var $datum DiagnosticOrderLab*/
            $service = new OrderService();
            /*@var $batch BatchOrderNote*/
            $batch = $datum->orderBatch;
            $referenceNo = $service->generateRefNo($datum);

            if (empty($referenceNo)) {

                $header = new PharmaHeader();
                $referenceNo = $service->generateSysId($header);
                $header->refno = $referenceNo;
                $header->encounter_no = $batch->encounter_no;
                $header->spin = $batch->encounterNo->spin;
                $header->create_id = $this->user->personnel_id;
                $header->modify_dt = date('Y-m-d H:i:s');
                $header->create_dt = date('Y-m-d H:i:s');
                $header->modify_id = $this->user->personnel_id;

                if ( ! $header->save()) {
                    throw new Exception('Unable To save Pharma Header');
                }
            }

            $detail = new PharmaDetails();
            $detail->id = Str::uuid();
            $detail->refno = $referenceNo;
            $detail->item_id = $datum->item_id;
            $detail->quantity = $datum->quantity;
            $header->create_id = $this->user->personnel_id;
            $header->modify_dt = date('Y-m-d H:i:s');
            $header->create_dt = date('Y-m-d H:i:s');
            $header->modify_id = $this->user->personnel_id;

            if ( ! $detail->save()) {
                throw new Exception('Unable To save Pharma detail');
            }

            $kardex = new KardexMedication();
            $kardex->id = Str::uuid();
            $kardex->diagnosticorder_id = $datum->id;
            $kardex->quantity = $datum->quantity;
            $kardex->item_id = $datum->item_id;
            $kardex->carryout_dt = date('Y-m-d H:i:s');
            $kardex->refno = $referenceNo;
            $kardex->nurse_id = $this->user->personnel_id;

            if ( ! $kardex->save()) {
                throw new Exception('Unable To save Kardex Medication');
            }
        }

    }
}