<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/21/2019
 * Time: 1:14 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $id
 * @property String $order_batchid
 * @property String $repetitive_procedure
 * @property String $remarks
 * @property String $session_start_date
 * @property String $session_end_date
 * @property String $session_start_time
 * @property String $session_end_time
 * @property String $history
 * @property String $create_id
 * @property String $create_dt
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $is_deleted
 */
class RepetitiveOrder extends Model
{
    protected $table = 'smed_repetitive_session';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function batch()
    {
        return $this->belongsTo(BatchOrderNote::class, 'order_batchid','id');
    }
}