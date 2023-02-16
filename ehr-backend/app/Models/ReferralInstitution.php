<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string $encounter_no
 * @property string $name_of_hci
 * @property string $referral_reason
 * @property int $is_hci
 * @property string $modify_id
 * @property datetime $modify_dt
 * @property string $create_id
 * @property datetime $create_dt
 */
class ReferralInstitution extends Model
{
    protected $table = 'smed_referral_institution';

    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'encounter_no',
        'name_of_hci',
        'referral_reason',
        'is_hci',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];
}
