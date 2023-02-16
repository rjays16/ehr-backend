<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $area_code
 * @property String $area_desc
 * @property String $dept_id
 * @property String $is_ward
 * @property String $is_ER
 * @property String $is_OP
 * @property String $is_OR
 * @property String $is_deleted
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
 * @property String $is_refer
 */
class AreaCatalog extends Model
{
    protected $table = 'smed_area_catalog';

    protected $primaryKey = 'area_id';
    public $timestamps = false;

    protected $fillable = [
        'area_code',
        'area_desc',
        'dept_id',
        'is_ward',
        'is_ER',
        'is_OP',
        'is_OR',
        'is_deleted',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
        'is_refer',
    ];

    public function department()
    {
        $model = self::query()
                ->select('area_id', 'area_desc' ,'admit_outpatient','admit_inpatient')
                ->where('is_refer', 1)
                ->where('is_active', 0)
                // ->where('status', '!=', "hidden")
                ->orderBy('area_desc', 'ASC')
                ->get();
        
        $data = [];
        foreach ($model as $key => $val) {
            $data[] = [
                'id' => $val['area_id'],
                'area_desc' => $val['area_desc'],
                'admit_outpatient' => $val['admit_outpatient'],
                'admit_inpatient' => $val['admit_inpatient'],
            ];
        }

        return $data;
    }

    public function dept(){
        return $this->belongsTo(AreaCatalog::class, 'dept_id', 'area_id');
    }

    public function depts(){
        return $this->hasMany(AreaCatalog::class, 'dept_id', 'area_id');
    }
}
