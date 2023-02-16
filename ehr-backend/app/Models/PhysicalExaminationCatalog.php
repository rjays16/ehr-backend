<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalExaminationCatalog extends Model
{
    protected $table = 'smed_physical_ex_catalog';

    public $timestamps = false;
    protected $primaryKey = 'id';

    // protected $fillable = [
    //     'phic_id',
    //     'phys_name',
    //     'phic_id_active',
    //     'findings_sequence',
    //     'category_sequence',
    //     'category',
    //     'is_default',
    //     'is_others',
    // ];

    protected $hidden = [
        'phic_id_active',
        'findings_sequence',
        'category_sequence',
        'is_default',
        'is_others',
    ];
}
