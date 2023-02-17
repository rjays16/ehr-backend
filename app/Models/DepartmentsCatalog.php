<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $dept_code
* @property string $dept_name
* @property int $is_active
* @property string $modify_id
* @property datetime $modify_dt
* @property string $create_id
* @property datetime $create_dt
* @property int $is_ehr
 */
class DepartmentsCatalog extends Model
{
    protected $table = 'smed_departments_catalog';

    protected $primaryKey = 'dept_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'dept_code',
        'dept_name',
        'is_active',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
        'is_ehr',
    ];
}
