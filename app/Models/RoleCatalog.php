<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $role_id
 * @property String $role_name
 * @property String $role_desc
 * @property String $role_area
 * @property String $is_allowed
 * @property String $is_deleted
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
 */
class RoleCatalog extends Model
{
    protected $table = 'smed_role_catalog';

    protected $primaryKey = 'role_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'role_name',
        'role_desc',
        'role_area',
        'is_allowed',
        'is_deleted',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];

    public function personnelAssignment(){
        return $this->hasMany(PersonnelAssignment::class, 'role_id', 'role_id');
    }
}
