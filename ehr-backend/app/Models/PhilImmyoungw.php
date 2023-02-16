<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilImmyoungw extends Model
{

    public $table = 'smed_phil_immyoungw';

    public $fillable = [
        'id',
        'imm_code',
        'imm_description',
        'library_status'
    ];


    public function getImmYoungData(){
        $query = self::query()->where("library_status", 1)->get();
        $immyoung = [];
        foreach ($query as $key => $entry){
            $immyoung[] = [
                'id' => $entry->id,
                'imm_code' => $entry->imm_code,
                'imm_description' => $entry->imm_description,
            ];
        }
        return $immyoung;
    }

}