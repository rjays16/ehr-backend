<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/25/2019
 * Time: 1:40 PM
 */

namespace App\API\V1\Controllers;

use App\API\V1\Services\EhrResponse;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\User\NotificationService;

class NotificationController extends Controller
{
    public function getNotifs(Request $request)
    {
        try {

            $service = new NotificationService;
            $result = $service->getNotifications(auth()->user()->username);

            return EhrResponse::jsonResponsePure($result);
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function setNotifSeen(Request $request)
    {
        try {

            $service = new NotificationService;
            $service->setNotifSeen(auth()->user()->username, $request->post('notif_id'));

            return EhrResponse::jsonSuccess('Success');
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function setNotifSeenByRefNo(Request $request)
    {
        try {

            $service = new NotificationService;
            $service->setNotifSeenByRefNo(auth()->user()->username, $request->post('refno'));

            return EhrResponse::jsonSuccess('Success');
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function setNotifSeenAll(Request $request)
    {
        try {

            $service = new NotificationService;

            $service->setNotifSeenAll(auth()->user()->username);

            return EhrResponse::jsonSuccess('Success');
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

}
