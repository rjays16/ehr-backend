<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilImmchild extends Model
{
    public $table = 'smed_phil_immchild';

    public $fillable = [
        'id',
        'imm_code',
        'imm_description',
        'library_status'
    ];


    public function getImmChildData(){
        $query = self::query()->where("library_status", 1)->get();
        $immchild = [];
        foreach ($query as $key => $entry){
            $immchild[] = [
                'id' => $entry->id,
                'imm_code' => $entry->imm_code,
                'imm_description' => $entry->imm_description,
            ];
        }
        return $immchild;
    }
}