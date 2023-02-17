<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/24/2019
 * Time: 2:41 PM
 */

namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorOrderService;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use Psy\Util\Json;

class DischargeController extends Controller
{
    public function saveDischargeOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result = $service->saveDischargeOrders($request->post('data'));

            $result = collect([])->put("data", $result);
            DB::commit();
            return EhrResponse::jsonSuccess($result->get('data')['message'], $result);
        } catch (EhrException $e) {
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function deleteDischargeOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result = $service->deleteDischargeOrders($request->post('data'));

            $result = collect([])->put("data", $result);
            DB::commit();
            return EhrResponse::jsonSuccess($result->get('data')['message'], $result);
        } catch (EhrException $e) {
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }
}