<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\Controller;

Route::get('/', function () {
    return view('welcome');
});


Route::group(['middleware' => ['web.report']], function(){
    Route::get('/doctor/patient/diagnostic/rad/report/pdf', function ()
    {
        $service = new App\API\V1\Controllers\Diagnostic\DiagnosticController();
        return $service->getRadReportPdf(request());
    });

    Route::get('/doctor/patient/diagnostic/lab/report/pdf', function ()
    {
        $service = new App\API\V1\Controllers\Diagnostic\DiagnosticController();
        return $service->getLabReportPdf(request());
    });


    Route::get('/printCf4', function ()
    {
        $service = new App\API\V1\Controllers\ReportsController();
        return $service->generateCf4(request());
    });


    Route::get('/doctor/patient/prescription', function ()
    {
        $service = new App\API\V1\Controllers\ReportsController();
        return $service->prescription(request());
    });


    
});


Route::group(['middleware' => ['web.telemed.report']], function(){
    Route::get('/telemed/prescription', function ()
    {
        $service = new App\API\V1\Controllers\ReportsController();
        return $service->prescription(request());
    });
    
});


Route::get('/mobile/manual', function ()
{
    $service = new App\API\V1\Controllers\ReportsController();
    return $service->mobileManual(request());
});




Route::get('/logs', function ()
{
    $service = new Controller;
    return $service->logs(request());
});

