<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:24 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class DiagnosisProcedure extends Model
{
    protected $table = 'smed_diagnosis_procedure';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function procedure()
    {
        $model = self::query()
                ->select('id', 'procedure', 'rvs_code')
                ->orderBy('id', 'ASC')
                ->get();

        $data = $temp = [];
        foreach ($model as $key => $val) {
            $temp['id'] = $val['id'];
            $temp['procedure'] = $val['procedure'];
            $temp['rvs_code'] = $val['rvs_code'];
            $data[] = $temp;
        }

        return $data;
    }
}