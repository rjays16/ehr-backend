<?php


namespace App\Services\Personnel;


use App\Exceptions\EhrException\EhrException;
use App\Models\AreaCatalog;
use App\Models\PersonnelCatalog;
use App\Models\PersonnelPermission;

class PersonnelService
{
    private $_personnel_id;

    public function __construct($_personnel_id)
    {
        $this->$_personnel_id = PersonnelCatalog::query()->where("personnel_id", $_personnel_id);
        if(!$this->$_personnel_id)
            throw new EhrException('Personnel does not exist.', 404);
    }

    public function getPersonnelCatalog_Assignment($personnel_id){
        $personnelData = PersonnelCatalog::query()->where("personnel_id", $personnel_id)
            ->with('assignments')
            ->first();
        return $personnelData;
    }

    public function getPersonnelPermission($personnel_id){
        $personnelPermission = PersonnelPermission::query()->where("personnel_id", $personnel_id)->get();
        return $personnelPermission;
    }

    public function getPersonnelAreaCatalog_department($area_id){
        $personnelDept_id = (AreaCatalog::query()
            ->where("area_id", $area_id)
            ->get());
        return $personnelDept_id;
    }

}