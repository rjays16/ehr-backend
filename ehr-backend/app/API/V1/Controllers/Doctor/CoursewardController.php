<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/25/2019
 * Time: 1:40 PM
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

class CoursewardController extends Controller
{
    public function saveCoursewardOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result = $service->saveCoursewardOrder($request->post('data'));

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


    public function deleteCoursewardOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = DoctorOrderService::init($request->input('encounterNo'));

            $result = $service->deleteCoursewardOrder($request->input('orders'));

            DB::commit();
            $result = collect([])->put("data", $result);
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