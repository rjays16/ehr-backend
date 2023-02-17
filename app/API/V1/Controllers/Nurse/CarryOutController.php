<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/6/2019
 * Time: 12:48 PM
 */

namespace App\API\V1\Controllers\Nurse;

use App\API\V1\Services\EhrResponse;
use App\API\V1\Services\NurseCarryoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Exceptions\EhrException\EhrException;
use App\Services\Nurse\NurseService;

class CarryOutController extends Controller
{
    public function getAllOrders(Request $request)
    {
        try {
            DB::beginTransaction();

            $nurse_service = NurseService::init($request->input('encounterNo'));

            $data = $nurse_service->getAllOrders();

            return EhrResponse::jsonResponsePure(['carryoutOrders' => $data]);
        } catch (EhrException $e) {
            DB::rollBack();

            return EhrResponse::jsonResponse(
                $e->getMessage(), $e->getCode(),
                $e->getRespDataJson(), $e->getTrace()
            );
        } catch (Exception $e) {
            DB::rollBack();

            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function carryOutOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            $service = new NurseCarryoutService();
            $service->carryOutOrder($request);
            DB::commit();

            JsonResponse::create(['code' => 200, 'success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            JsonResponse::create(['error' => $e->getMessage()]);

        } finally {
            JsonResponse::create(['data' => $request]);
        }
    }

}