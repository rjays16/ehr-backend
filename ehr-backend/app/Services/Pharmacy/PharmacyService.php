<?php


namespace App\Services\Pharmacy;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\PatientCatalog;
use App\Models\PersonCatalog;
use App\Models\PhilMedicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmacyService
{

    /**
     * @return array
     */
    public function search($query){
        $query = trim($query);
        
        if(empty($query) || strlen($query) < 3)
            return [];

        $result = PhilMedicine::query()
            ->select([
                'drug_code as id',
                'drug_code as code',
                'description as name',
                'gen_code as group',
                'description as groupName',
                'gen_code as groupCode',
            ])
            ->where('description','like',"%{$query}%")
            ->limit(20)
            ->get()            
            ->sort(function($a, $b) use ($query){
                $a = Str::startsWith($query, $a->name);
                $b = Str::startsWith($query, $b->name);
                return $a ? -1 : 1;
            });
        return $result->toArray();
    }


    
}