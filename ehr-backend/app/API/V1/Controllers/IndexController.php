<?php


namespace App\API\V1\Controllers;


use App\Http\Controllers\Controller;
use Dingo\Api\Contract\Http\Request;

class IndexController extends Controller
{

    public function healthCheck(Request $request)
    {
        $data = [
            'success' => true,
            'health' => 'Up and running',
            'version' => env('APP_VESION', 'v1'),
            'message' => env('APP_DESCRIPTION', 'Welcome to EHR BACKEND API!')
        ];
        return response()->json(compact('data'));
    }

    public function about(Request $request)
    {
        $data = [
            'success' => true,
            'health' => 'Up and running',
            'version' => env('APP_VESION', 'v1'),
            'message' => env('APP_DESCRIPTION', 'Welcome to Segworks EHR!')
        ];
        return response()->json(compact('data'));
    }


}
