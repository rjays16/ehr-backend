<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilImmelderly extends Model
{
    public $table = 'smed_phil_immelderly';

    public $fillable = [
        'id',
        'imm_code',
        'imm_description',
        'library_status'
    ];


    public function getImmElderlyData(){
        $query = self::query()->where("library_status", 1)->get();
        $immElderly = [];
        foreach ($query as $key => $entry){
            $immElderly[] = [
                'id' => $entry->id,
                'imm_code' => $entry->imm_code,
                'imm_description' => $entry->imm_description,
            ];
        }
        return $immElderly;
    }

}