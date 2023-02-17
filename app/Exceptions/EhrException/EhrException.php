<?php
/**
 * Created by PhpStorm.
 * User: segworks-bonix
 * Date: 4/28/2019
 * Time: 9:43 PM
 */
namespace App\Exceptions\EhrException;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EhrException extends HttpException
{
    private $_otherData;
    public $stamp;
    public $log;
    public function __construct($message = "", $code = 500, $otherData = [], $log_this=false, \Throwable $previous = null, array $headers = []) {
        $this->_otherData = $otherData;
        $this->stamp = date('Y-m-d h:i:sa');

        parent::__construct($code, $message, $previous, $headers, $code);

        // if($log_this)
            $this->_log();
    }

    public  function getRespDataJson(){
        return array_merge($this->_otherData, [
            'code' => $this->getCode(),
            'msg' => $this->getMessage(),
        ]);
    }

    /*
     * Log to application.log errors
     * including traces and file error throws and line number
     * */
    private function _log(){
        new EhrLogException($this, $this->_otherData);
    }



}