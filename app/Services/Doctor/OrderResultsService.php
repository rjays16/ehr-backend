<?php


namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\Encounter;
use App\Services\Doctor\Permission\PermissionService;

class OrderResultsService
{

    /**
     * @var Encounter $encounter
     */
    private $encounter;
    public function __construct(Encounter $encounter)
    {
        $this->encounter = $encounter;
    }

    /**
     * @return OrderResultsService
     */
    public static function init($encounter)
    {
        $enc = Encounter::query()->find($encounter);
        if(is_null($enc))
            throw new EhrException('Encounter not found.');

        return new OrderResultsService($enc);
    }


    private function _getAllHISResults(){
        $his = HisActiveResource::instance();
        $resp = $his->getAllPatientResults($this->encounter->encounter_no);
        return $resp;
    }

    public function getResulst(){
        $results_lab = [];
        $results_rad = [];

        $resp = $this->_getAllHISResults();
        
        if ($resp['status']) {
            foreach (collect($resp['data'])->recursive()->toArray() as $labkey => $lab) {
                if (!in_array($lab['ref_source'], array('RAD')))

                    $results_lab[] = [
                        'index' => count($results_lab),
                        'refno' => $lab['refno'],
                        'service' => $lab['service_name'],
                        'date_uploaded' => $lab['date_update'],
                        'pid' => $lab['pid'],
                        'status' => $lab['status'],
                        'batch_nr_grp' => $lab['batch_nr_grp'],
                        'request_date' => $lab['request_date'],
                        'service_date' => $lab['service_date'],
                        'filename' => $lab['filename'],
                        'pacs_order_no' => $lab['pacs_order_no'],
                        'lis_order_no' => $lab['lis_order_no'],
                        'is_served' => $lab['is_served'],
                        'ref_source' => $lab['ref_source'],
                    ];
                else
                    $results_rad[] = [
                        'index' => count($results_rad),
                        'refno' => $lab['refno'],
                        'service' => $lab['service_name'],
                        'date_uploaded' => $lab['date_update'],
                        'pid' => $lab['pid'],
                        'status' => $lab['status'],
                        'in_pacs' => $lab['in_pacs'],
                        'pacs_code' => $lab['pacs_code'],
                        'batch_nr_grp' => $lab['batch_nr_grp'],
                        'request_date' => $lab['request_date'],
                        'service_date' => $lab['service_date'],
                        'filename' => $lab['filename'],
                        'pacs_order_no' => $lab['pacs_order_no'],
                        'lis_order_no' => $lab['lis_order_no'],
                        'is_served' => $lab['is_served'],
                        'ref_source' => $lab['ref_source'],
                    ];
            }
        }
        return [
            'results_lab' => $results_lab,
            'results_rad' => $results_rad
        ];
    }

    

    public function getResulst_forportlet(){
        $results = $this->getResulst();
        // group labresulst by refno
        $labs = []; $labs_refs = [];

        foreach ($results['results_lab'] as $result) {
            if(!in_array($result['refno'], $labs_refs))
                $labs_refs[] = $result['refno'];
        }

        foreach ($labs_refs as $refno) {
            $labs[] = array_filter($results['results_lab'], function($item) use ($refno){
                return $item['refno'] == $refno ;
            });
        }

        $final_labs = [];
        foreach ($labs as $refn) {
            $d = [];
            foreach ($refn as $refn2) {
                $d = [
                    'index' => count($final_labs),
                    'refno' => $refn2['refno'],
                    'service' => isset($d['service']) ? $d['service'].', '.$refn2['service'] : $refn2['service'],
                    'date_uploaded' => $refn2['date_uploaded'],
                    'pid' => $refn2['pid'],
                    'status' => $refn2['status'],
                    'batch_nr_grp' => $refn2['batch_nr_grp'],
                    'request_date' => $refn2['request_date'],
                    'service_date' => $refn2['service_date'],
                    'filename' => $refn2['filename'],
                    'pacs_order_no' => $refn2['pacs_order_no'],
                    'lis_order_no' => $refn2['lis_order_no'],
                    'is_served' => $refn2['is_served'],
                    'ref_source' => $refn2['ref_source'],
                ];
            }
            $final_labs[] = $d;
        }


        return [
            'results_lab' => $final_labs,
            'results_rad' => $results['results_rad']
        ];
    }


    /**
     * @return \Illuminate\Http\Response
     */
    public function labReportPDF($pid, $lis_order_no)
    {
        return response()->make($this->labReportPDFData($pid, $lis_order_no), 200,[
            'Content-type' => "application/pdf",
            'Content-disposition' => "inline;filename=labReport".date('Ymd').".pdf",
            'Content-Transfer-Encoding' => "binary",
            'Accept-Ranges' => "bytes",
        ]);
    }


    public function labReportPDFData($pid, $lis_order_no)
    {
        return file_get_contents(env('HIS_URL_BASE','localhost/hisdmc')."/modules/laboratory/seg-lab-report-hl7.php?pid={$pid}&lis_order_no={$lis_order_no}&showBrowser=1");
    }


    /**
     * @return \Illuminate\Http\Response
     */
    public function radReportPDF($pid, $batch_nr_grp)
    {
        return response()->make($this->radReportPDFData($pid, $batch_nr_grp), 200,[
            'Content-type' => "application/pdf",
            'Content-disposition' => "inline;filename=radReport".date('Ymd').".pdf",
            'Content-Transfer-Encoding' => "binary",
            'Accept-Ranges' => "bytes",
        ]);
    }

    public function radReportPDFData($pid, $batch_nr_grp)
    {
        return file_get_contents(env('HIS_URL_BASE','localhost/hisdmc')."/modules/radiology/certificates/seg-radio-report-pdf.php?ntid=false&lang=en&pid={$pid}&batch_nr_grp={$batch_nr_grp}&showBrowser=1");
    }


    /**
     * @return string
     */
    public function radPacsUls($refno)
    {

        if(env('APP_DEBUG') || env('API_DEBUG'))
            return env('APP_URL2')."/pacs-sample/rad_sample.html";

            
        $his = HisActiveResource::instance();
        $resp = $his->getRadioPacsResultUrl($refno);
        if($resp == false)
            throw new EhrException('Something is wrong.', 500, ['resp_date' => $his->getResponseData()]);

        

        return $resp['url'];
    }
    
}