<?php

namespace App\Http\Controllers;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\Permission\PermissionService;
use Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getBaseUrl(Request $request)
    {
        return "{$this->getBaseHost($request)}{$request->getBasePath()}";
    }


    public function getBaseHost(Request $request)
    {
        return "{$request->getScheme()}://{$request->getHttpHost()}";
    }


    public function logs(Request $request)
    {

        $date = $request->input('date', date('Y-m-d'));

        $output = realpath(storage_path()."/app/logs/applogs_{$date}.log");
        if ($output === false) {
            throw new EhrException(
                "No Logs for this date {$date}", 404, [], true
            );
        }


        return response()->make(
            "<pre>".file_get_contents($output)."</pre>", 200, [
            'Content-disposition'       => "inline;filename=logs{$date}.txt",
            'Content-Transfer-Encoding' => "binary",
            'Accept-Ranges'             => "bytes",
        ]
        );
    }
}
