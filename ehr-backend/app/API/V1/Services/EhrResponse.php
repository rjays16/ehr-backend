<?php


namespace App\API\V1\Services;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\Permission\PermissionService;
use Exception;
use Illuminate\Support\Collection;

class EhrResponse
{
    /**
     * @return array
     */
    public static function responseFormat($message = "", $code = 200,
        $otherData = [], $traces = [], $e = null
    ) {

        $resp_data = [
            'code'    => $code,
            'status'  => $code == 200 ? true : false,
            'saved'   => $code == 200 ? true : false,
            'message' => $message,
        ];


        if ($code != 200) {
            /**
             * @var \Illuminate\Http\Request $request
             */
            $request = request();
            $reqData = collect($request->json()->all());
            if (count($reqData->toArray()) > 0) {
                $request->initialize($request->query(), $reqData->toArray());
            }
            $reqData = $reqData->merge($request->all())->merge($request->query())->merge($_POST)->merge($_GET);
            $resp_data['request_data'] = $reqData->toArray();

            EhrLogException::logMessage(
                "DEBUG LOG: {$message} ({$code})", $resp_data
            );

        }


        if (count($traces) > 0) {
            $resp_data['traces'] = $traces;
        }

        if ( ! is_null($e)) {
            $resp_data['traces'] = $e->getTrace();

            if ($e instanceof EhrException) {
                /**
                 * @var EhrException $e
                 */
                $resp_data['exception_data'] = $e->getRespDataJson();
            }

        }

        if (!config('app.debug')) {
            $resp_data = collect($resp_data)->forget([
                "request_data",
                "traces",
                "exception_data",
            ])->toArray();
        }


        if ($otherData instanceof Collection) {
            /**
             * @var Collection $otherData
             */
            $otherData = $otherData->merge($resp_data);
            $otherData = $otherData->toArray();
        } else {
            $otherData = array_merge($resp_data, $otherData);
        }

        if($code == PermissionService::$errorCode){
            $otherData['permissions'] = PermissionService::$ehrPermissions;
        }

        if ($code >= 500 || ! is_int($code)) {
            $code = 500;
        }

        return [
            'data' => $otherData,
            'code' => $code,
        ];
    }



    /**
     * @param Exception $e
     * @return array
     */
    public static function responseExceptionFormat($e)
    {
        return self::responseFormat(
            $e->getMessage(), $e->getCode(), [], [], $e
        );
    }

    public static function jsonResponse($message = "", $code = 200, $otherData = [],
        $traces = []
    ) {
        $format = self::responseFormat($message, $code, $otherData, $traces);

        return response()->json($format['data'])->setStatusCode(
            $format['code']
        );
    }

    public static function jsonResponsePure($data = [], $code = 200, $traces = [])
    {
        return response()->json(count($traces) > 0 ? $traces : $data)
            ->setStatusCode($code);
    }

    public static function jsonSuccess($message = '', $otherData = [], $traces = [])
    {
        return self::jsonResponse(
            $message,
            200,
            $otherData,
            $traces
        );
    }

    public static function jsonError404($message = '', $otherData = [], $traces = [])
    {
        return self::jsonResponse(
            $message,
            404,
            $otherData,
            $traces
        );
    }

    public static function jsonError401($message = '', $otherData = [], $traces = [])
    {
        return self::jsonResponse(
            $message,
            401,
            $otherData,
            $traces
        );
    }

    public static function jsonError500($message = '', $otherData = [], $traces = [])
    {
        return self::jsonResponse(
            $message,
            500,
            $otherData,
            $traces
        );
    }
}