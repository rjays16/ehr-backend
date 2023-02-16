<?php

namespace App\Models\Mongo;

use Illuminate\Database\Query\Builder;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
/**
 *  @property string $consult_id
 *  @property string $data
 *  @property int $sent_timestamp
 *  @property string $sender_username
 *  @property string $sender_player_id
 *  @property string $ack_by_spmc
 *  @property string $ack_by_patient
 *  @property string $ack_timestamp
 *  @property boolean $is_ack
 *  @property boolean $for_mobile
 *  @property string $real_notif_id
*/
class ConsultationNotification extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'consultation_notifications';
    protected $primaryKey = "_id";

    protected $fillable = [
        'consult_id',
        'data',
        'sent_timestamp',
        'sender_username',
        'sender_player_id',
        'ack_by_spmc',
        'ack_by_patient',
        'ack_timestamp',
        'is_ack',
        'for_mobile',
        'real_notif_id',
    ];
}
