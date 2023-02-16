<?php

namespace App\Models\Mongo;

use DateTime;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 * @property object $_id
 * @property string $id
 * @property string $orderBatch
 * @property string $encounterNo
 * @property DateTime $date
 * @property string $color
 * @property string $note
*/
class OrderNotes extends Eloquent
{
    use SoftDeletes;

    protected $form_code = 'PA';

    protected $connection = 'mongodb';
    protected $collection = 'order_notes';

    protected $fillable = [
        //'_id',
        'id',
        'orderBatch',
        'encounterNo',
        'date',
        'color',
        'note',
    ];

    protected $casts = [
        // 'id' => 'string',
        // 'orderBatch'=> 'string',
        // 'encounterNo'=> 'string',
        // 'date'=> 'datetime',
        // 'color'=> 'string',
        // 'note'=> 'string',
    ];
}
