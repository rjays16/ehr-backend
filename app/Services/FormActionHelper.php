<?php
namespace App\Services;

use App\Models\PersonnelCatalog;

class FormActionHelper
{

    public static function getFormTimeStamp($key = "")
    {
        return [
            "{$key}modified_dt" => date('Y-m-d H:i:s'),
            "{$key}modified_by" => auth()->user()->personnel->personnel_id,
        ];
    }


    public static function getModifier($key, $data)
    {

        $id = $data['modified_by'];
        $date = $data['modified_dt'];

        $modified_by =  PersonnelCatalog::query()->find($id);

        if(!$data || is_null($modified_by))
            return [
                "{$key}modified_dt" => '',
                "{$key}modified_by" => '',
                "{$key}modified_by_fullname" => ''
            ];

        if(strpos(strtolower($date) ,'am') == true || strpos(strtolower($date), 'pm') == true)
            $modified_dt  = $date;
        else{
            $thisentry = new \DateTime($date);
            $modified_dt = date('m-d-Y h:i a', ($thisentry->getTimestamp() * 1));
        }

        return [
            "{$key}modified_dt" => $modified_dt,
            "{$key}modified_by" => $modified_by->p->getFullname().' '.$modified_dt,
            "{$key}modified_by_fullname" => $modified_by->p->getFullname(),
        ];
    }

}
