<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilDisease extends Model
{

    public $table = 'smed_phil_disease';

    public $fillable = [
        'disease_id',
        'mdisease_code',
        'mdisease_description',
        'library_status'
    ];


    public function getPhilDiseases(){
        $result = self::query()->where("library_status", 1)->get();
        $disease = [];
        foreach ($result as $key => $entry){
            $disease[] = [
                'id' => $entry->disease_id,
                'mdisease_code' => $entry->mdisease_code,
                'mdisease_description' => $entry->mdisease_description,
            ];
        }
        return $disease;
    }

}