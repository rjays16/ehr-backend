<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 3:17 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $table = 'smed_transaction_type';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $timestamps = false;

    static $trans_types;

    protected $fillable = [
            'id',
            'charge_name',
            'description',
            'create_id',
            'create_dt',
            'modify_id',
            'modify_dt',
    ];

    public function TransactionType($defaults = false)
    {
        $model = self::query()
                ->select('id', 'charge_name')
                ->whereNotIn('id',[
                    'charity',
                    'cmap',
                    'dost',
                    'lingap',
                    'paid',
                    'phs',
                    'sdnph'
                ])
                ->get();

        $data = $temp = [];
        foreach ($model as $key => $val) {
            $temp['id'] = $val['id'];
            $temp['charge_name'] = $val['charge_name'];
            $data[] = $temp;
        }

        return $data;
    }

    public static function getChargeType($charge_type)
    {
        if(is_null(self::$trans_types))
            self::$trans_types = collect(self::query()->get()->toArray());
        
        $chargeType = self::$trans_types->where('id', $charge_type)->first();
        $charge_type_desc =  $chargeType? $chargeType['charge_name'] : $charge_type;
        return $charge_type_desc;
    }
}