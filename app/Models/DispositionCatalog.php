<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $disposition_desc
 * @property $is_default
 * @property $is_deleted
 * @property $create_id
 * @property $create_dt
 * @property $modify_id
 * @property $modify_dt
 */

class DispositionCatalog extends Model
{
    const NOT_DELETED = 0;

    public $table = 'smed_disposition_catalog';

    public $fillable = [
        'id',
        'disposition_desc',
        'is_default',
        'is_deleted',
        'create_id',
        'create_dt',
        'modify_id',
        'modify_dt',
    ];


    public function getAllOptions($defaults = false) {

        $model= self::query()->where('is_default',1)->get();
        $data = $temp = array();
        foreach ($model as $key => $val) {
            $temp['id'] = $val['id'];
            $temp['text'] = $val['disposition_desc'];
            $data[] = $temp;
        }
        return $data;
    }

}