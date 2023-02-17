<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PertinentSignSymptomsOnDiagnosisCatalog extends Model
{
    protected $table = 'smed_pertinent_symp_admis_catalog';

    public $timestamps = false;

    protected $fillable = [
        'chiefcomp_id',
        'psa_name',
        'is_default',
        'is_pain',
        'is_others',
    ];

    public function chiefcomp()
    {
        return $this->belongsTo(ChiefComplaintCatalog::class, 'chiefcomp_id', 'id');
    }


    /*
     * @var Array $ids  => [1,2,3,4]
     * */
    public function getPertinentSASonAdmisSelected($ids)
    {
        $entries = [];
        $entries_phic_ids = [];
        $entries_names = [];
        $column = -1;
        $other_pains = [];
        $others = [];
        if(count($ids) > 0){
            foreach (self::query()->whereIn('id', $ids)->get() as $key => $entry){

                if($entry->is_pain == 1){
                    $other_pains[]  = $entry->psa_name;
                }
                else if($entry->is_others == 1){
                    $others[]  = $entry->psa_name;
                }
                else{
                    $entries[] = $entry->id;
                    $entries_phic_ids[] = $entry->chiefcomp_id;
                    $entries_names[] = $entry->psa_name;
                }
            }
            if(count($other_pains) > 0){
                $entries_phic_ids[] = Config::getConfig('chief_comp_pain_id'); // getting PAIN chief complaint id
            }
        }


        return [
            'data' => $entries,
            'data_phic' => $entries_phic_ids,
            'data_name' => $entries_names,
            'opt_2' => $other_pains,
            'opt_3' => $others,
        ];
    }



    public function getPertinentSASonAdmisDefaultCatalog()
    {
        $data = [];
        foreach (self::query()->where('is_default',1)->get() as $key => $entry){
            $data[$entry->id] = $entry->psa_name;
        }

        return $data;
    }

    public function getPertinentSASonAdmisDefaultDiags()
    {
        $entries = []; $column = -1;
        $other_pains = [];
        $others = [];
        foreach (self::query()->get() as $key => $entry){
            if($key % 9 == 0){
                $column += 1;
            }

            if($entry->is_pain == 1){
                $other_pains[]  = $entry->psa_name;
            }
            else if($entry->is_others == 1){
                $others[]  = $entry->psa_name;
            }
            else{
                $entries[$column][$entry->id] = $entry->psa_name;
            }
        }


        return [
            'data' => $entries,
            'opt_2' => $other_pains,
            'opt_3' => $others,
        ];
    }


    public function isIdeExist($id){
        return !self::query()->find($id) ? false : true;
    }

    public function isNameExist($name){
        return self::query()->where('psa_name',$name)->first();
    }


    public function newCatalog($name, $is_pain, $is_others){
        $model = new PertinentSignSymptomsOnDiagnosisCatalog();
        $model->psa_name = $name;
        $model->is_default = 0;
        $model->is_pain = $is_pain;
        $model->is_others = $is_others;

        return !$model->save() ? 0 : $model->id;
    }
}
