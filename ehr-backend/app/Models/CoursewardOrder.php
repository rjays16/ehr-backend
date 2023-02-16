<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursewardOrder extends Model
{
    protected $table = 'smed_courseward_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function orderBatch()
    {
        return $this->belongsTo(BatchOrderNote::class,'order_batchid');
    }
}
