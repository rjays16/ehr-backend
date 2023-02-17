<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NurseWardCatalog extends Model
{
    const IS_INACTIVE = "inactive";
    const IS_TEMP_CLOSED = "1";
    public $table = 'smed_nurse_ward_catalog';
    public $timestamps = false;

    //Relationships

    //QUERIES
    public function getWards(){
        $query = self::query()->where([
                ["status", "<>", self::IS_INACTIVE],
                ["is_temp_closed", "<>", self::IS_TEMP_CLOSED]
            ]
        )
        ->get();

        $wards = [];
        foreach ($query as $key => $entry){
            $wards[] = [
                'nr' => $entry->nr,
                'ward_id' => $entry->ward_id,
                'name' => $entry->name,
                'description' => $entry->description,
                'dept_id' => $entry->description
            ];
        }
        return $wards;
    }
}
