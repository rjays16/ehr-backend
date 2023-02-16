<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilImmpregw extends Model
{

    public $table = 'smed_phil_immpregw';

    public $fillable = [
        'id',
        'imm_code',
        'imm_description',
        'library_status'
    ];


    public function getImmPregwData(){
        $query = self::query()->where("library_status", 1)->get();
        $immPregw = [];
        foreach ($query as $key => $entry){
            $immPregw[] = [
                'id' => $entry->id,
                'imm_code' => $entry->imm_code,
                'imm_description' => $entry->imm_description,
            ];
        }
        return $immPregw;
    }
}