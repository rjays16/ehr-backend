<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property string $id
 * @property string permission_id
 * @property string personnel_id
 */
class PersonnelPermission extends Model
{
    public $table = 'smed_personnel_permission';

    public $fillable = [
        'id',
        'personnel_id',
        'permission_id',
        'is_deleted'
    ];


    public function permission()
    {
        return $this->belongsTo(PermissionCatalog::class,'permission_id','id' );
    }
}
