<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionCatalog extends Model
{
    protected $table = 'smed_permission_catalog';

    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'permission_id',
    ];

    public function personenlPermission()
    {
        return $this->hasMany(PersonnelPermission::class,'id','permission_id')
                    ->where('is_deleted',0);
    }
}
