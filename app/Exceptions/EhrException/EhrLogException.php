<?php
/**
 * Created by PhpStorm.
 * User: segworks-bonix
 * Date: 5/1/2019
 * Time: 10:24 PM
 */

namespace App\Exceptions\EhrException;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
class EhrLogException
{
    private static $logMessage = '';
    /**
     * @var Collection $otherData
     */
    private static $otherData;
    public function __construct($exception, $otherData = []){
        
        self::$logMessage  = (isset($exception->stamp)? $exception->stamp : date('Y-m-d h:i:sa')).' : '.(!auth()->guest() ? auth()->user()->username : '<Yii::app()->user undefined>').'('.(!auth()->guest() ? auth()->user()->id : '<Yii::app()->user undefined>').'): '.$exception->getMessage(). ' => '.$exception->getFile() . " ({$exception->getLine()})";
        
        self::$otherData = collect($otherData);
    }

    public static function logMessage($message = '', $otherData = [])
    {
        if(empty(self::$logMessage))
            self::$logMessage = $message;
        else
            self::$logMessage.="\n$message";
        
        if(self::$otherData)
            self::$otherData = self::$otherData->merge($otherData);
        else
            self::$otherData = collect($otherData);

        Storage::disk('local')->append("logs/applogs_".date('Y-m-d').".log", self::$logMessage);
        if(count(self::$otherData->toArray()) > 0)
            Storage::disk('local')->append("logs/applogs_".date('Y-m-d').".log", ('-> Data: '.print_r(self::$otherData->toArray(), true)));
    }
}