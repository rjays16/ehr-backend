<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/23/2019
 * Time: 7:54 AM
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

class RepetitiveSessionController extends Controller
{
    public function saveRepetitiveSession(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result = $service->saveRepetitiveSession($request->post('data'));

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

    public function deleteRepetitiveSession(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result =$service->deleteRepetitiveSession($request->post('data'));

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


    public function deleteRepetitiveCoursewardSession(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $service->deleteRepetitiveSession($request->post('data'));
            $result = $service->deleteRepetitiveCoursewardOrder($request->post('encounterCourseWardID'));

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