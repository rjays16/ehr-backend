<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
/**
 * @property String $id
 * @property String $personnel_id
 * @property String $area_id
 * @property String $role_id
 * @property String $dept_id
 * @property String $start_date
 * @property String $end_date
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
*/
class PersonnelAssignment extends Model
{
    protected $table = 'smed_personnel_assignment';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'personnel_id',
        'area_id',
        'role_id',
        'dept_id',
        'start_date',
        'end_date',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, 'personnel_id');
    }

    public function area(){
        return $this->belongsTo(AreaCatalog::class, 'area_id');
    }

    public function role(){
        return $this->belongsTo(RoleCatalog::class, 'role_id','role_id');
    }

    public static function checkRole($personnel_id)
    {
        return self::query()->where('personnel_id', $personnel_id)
            ->first();
    }
}
