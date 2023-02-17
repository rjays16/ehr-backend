<?php

namespace App\Utility;

use App\Exceptions\EhrException\EhrException;
use JavaClass;
use Java;

class JasperReport
{
    /**
     * Sample call
     * 
     *   $data[0] = array();
     *   $jasperReport = new Jasperreport();
     *   $jasperReport->showReport('reportDietary', $params, $data, 'PDF');
     * 
     */
    public function checkJavaExtension()
    {
        if (!extension_loaded('java')) {
            $sapi_type = php_sapi_name();
            $port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT']) > 1024)) ? $_SERVER['SERVER_PORT'] : '8080';
            if ($sapi_type == "cgi" || $sapi_type == "cgi-fcgi" || $sapi_type == "cli") {
                require_once(config('app.tomcat.java_include'));
                return true;
            } else {
                if (!(@require_once(config('app.tomcat.java_include')))) {
                    require_once(config('app.tomcat.java_include'));
                }
            }
        }
        if (!function_exists("java_get_server_name")) {
            throw new EhrException('The loaded java extension is not the PHP/Java Bridge');
        }
        return true;
    }


    public function showReport($template_name, $parameters, $tableData = array(), $repFormat = 'pdf')
    {
        $this->checkJavaExtension();
        $report = $template_name;
        $compileManager = new JavaClass("net.sf.jasperreports.engine.JasperCompileManager");
        $compileManager->__client->cancelProxyCreationTag = 0;
        $report = $compileManager->compileReport(realpath(config('app.tomcat.java_resource') . $report));
        java_set_file_encoding("UTF-8");
        $fillManager = new JavaClass("net.sf.jasperreports.engine.JasperFillManager");
        $params = new Java("java.util.HashMap");
        $start = microtime(true);

        #------------- DATA -------------------------------------------------------------------------------------
        #------------- DATA -------------------------------------------------------------------------------------

        foreach ($parameters as $key => $value) {
            $params->put($key, $value);
        }

        $data = $tableData;

        #------------- DATA -------------------------------------------------------------------------------------
        #------------- DATA -------------------------------------------------------------------------------------
        $jCollection = new Java("java.util.ArrayList");
        foreach ($data as $i => $row) {
            $jMap = new Java('java.util.HashMap');
            foreach ($row as $field => $value) {
                $jMap->put($field, $value);
            }
            $jCollection->add($jMap);
        }

        $jMapCollectionDataSource = new Java("net.sf.jasperreports.engine.data.JRMapCollectionDataSource", $jCollection);
        $jasperPrint = $fillManager->fillReport($report, $params, $jMapCollectionDataSource);
        $end = microtime(true);
        $outputPath  = tempnam(env('TOMCAT_TMP'), '');
        chmod($outputPath, 0777);

        header("Content-Type: text/html; charset=utf-8");
        if (strtoupper($repFormat) == 'PDF') {
            header("Content-Type: application/pdf");
            $exportManager = new JavaClass("net.sf.jasperreports.engine.JasperExportManager");
            $exportManager->exportReportToPdfFile($jasperPrint, $outputPath);
        } else {
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=output.xls");
            $exportManager = new java("net.sf.jasperreports.engine.export.JRXlsExporter");
            $exportManager->setParameter(java("net.sf.jasperreports.engine.JRExporterParameter")->JASPER_PRINT, $jasperPrint);
            $exportManager->setParameter(java("net.sf.jasperreports.engine.JRExporterParameter")->OUTPUT_FILE_NAME, $outputPath);
            $exportManager->exportReport();
        }

        readfile($outputPath);
        unlink($outputPath);
    }
}