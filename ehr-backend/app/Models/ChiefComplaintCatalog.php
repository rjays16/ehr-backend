<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiefComplaintCatalog extends Model
{
    protected $table = 'smed_chief_complaint_catalog';

    public $timestamps = false;

    protected $fillable = [
        'symptom_name',
        'librarystatus',
        'is_phil',
        'is_others',
    ];

    public function saveChiefCat($name){
        $exist = $this->isExist($name);
        if($exist)
            return $exist->id;

        return false;
    }

    public function isExist($name){
        return self::query()->where('symptom_name', $name)->where('librarystatus', 1)->where('is_deleted',0)->first();
    }



    public function getAllOptionsSeletecText($ids = []){
        return $this->_getAllOptionsSeletecText($ids)['names_list'];
    }

    private function _getAllOptionsSeletecText($ids = []){
        $names_assoc = [];
        $names_list = [];

        if(is_null($ids))
            $ids = [];

        if(!(count($ids) < 1 || !is_array($ids))){
            $result = self::query()->where('is_deleted',0)->where('librarystatus', 1)->whereIn('id',$ids)->get();
            foreach ($result as $entry){
                $names_list[] = $entry->symptom_name;
                $names_assoc[$entry->id] = $entry->symptom_name;
            }
        }

        return [
            'names_list' => $names_list,
            'names_assoc' => $names_assoc,
        ];
    }


    public function getAllOptionsActiveText(){
        $result = self::query()->where('is_deleted',0)->where('librarystatus',1)->get();
        $names = [];
        foreach ($result as $key => $entry){
            $names[] = [
                'id' => $entry->id,
                'name' => $entry->symptom_name,
                'isothers' => $entry->is_others ? true : false
            ];
        }
        return $names;
    }
}
