<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/**
* @property String $icd_code
* @property String $icd_desc
* @property bool $is_phic
* @property bool $is_deleted
* @property String $modify_dt
* @property DateTime $modify_id
* @property String $create_dt
* @property DateTime $create_id
 */
class Icd10Code extends Model
{
    protected $table = 'smed_icd10_code';

    protected $primaryKey = 'icd_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'icd_code',
        'icd_desc',
        'is_phic',
        'is_deleted',
        'modify_dt',
        'modify_id',
        'create_dt',
        'create_id',
    ];


    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0)
                ->orWhereNotNull('is_deleted')
                ->orderByDesc('icd_code');
    }

    


}
