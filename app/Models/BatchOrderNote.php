<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $is_finalized
 * @property string $encounter_no
 * @property string $doctor_id
 * @property string $encoder_id
 * @property DateTime $create_dt
 * @property DateTime $modify_dt
 */
class BatchOrderNote extends Model
{
    protected $table = 'smed_batch_order_note';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    protected $fillable = [
        'is_finalized',
        'encounter_no',
        'doctor_id',
        'encoder_id',
        'create_dt',
        'modify_dt',
    ];

    public function doctor()
    {
        return $this->belongsTo(PersonnelCatalog::class, 'doctor_id','personnel_id');
    }

    public function encounterNo()
    {
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function medsOrders()
    {
        return $this->hasMany(MedsOrder::class, 'order_batchid');
    }

    public function diagnosticOrderLabs()
    {
        return $this->hasMany(DiagnosticOrderLab::class,'order_batchid')
                    ->where('is_deleted',0)
                    ;
    }


    public function diagnosticOrderLab()
    {
        return $this->hasOne(DiagnosticOrderLab::class, 'order_batchid')
                    ->whereHas('auxiliaryServiceOrder');
    }

    public function diagnosticOrderRads()
    {
        return $this->hasMany(DiagnosticOrderRad::class, 'order_batchid')
                    ->where('is_deleted',0)
                    ;
    }

    public function diagnosticOrderRad()
    {
        return $this->hasOne(DiagnosticOrderRad::class, 'order_batchid')
               ->whereHas('auxiliaryServiceOrder');
    }

    public function auxiliaryServiceOrders()
    {
        return $this->hasMany(AuxiliaryServiceOrder::class, 'order_batchid');
    }

    public function auxiliaryServiceOrder()
    {
        return $this->hasOne(AuxiliaryServiceOrder::class, 'order_batchid');
    }

    public function courseWards()
    {
        return $this->hasMany(CoursewardOrder::class, 'order_batchid')
                    ->whereNull('deleted_at')
                    ->orderByDesc('order_dt');
    }

    public function servicePackageOrders()
    {
        return $this->hasMany(ServicePackageOrder::class, 'order_batchid');
    }

    public function dischargeOrder()
    {
        return $this->hasOne(DischargeOrder::class,'order_batchid')
                    ->where('is_deleted',0);
    }


    public function dischargeOrders()
    {
        return $this->hasOne(DischargeOrder::class,'order_batchid')
                    ->where('is_deleted',0);
    }

    public function referralOrder()
    {
        return $this->hasOne(ReferralOrder::class,'order_batchid')
                    ->where('is_deleted',0);
    }

    public function repetitiveOrder()
    {
        return $this->hasMany(RepetitiveOrder::class,'order_batchid')
                ->where('is_deleted', 0)
                ->orderBy('session_start_date');
    }

    public function repetitiveSessionOrders()
    {
        return $this->hasMany(SmedRepetitiveSession::class,'order_batchid')
                ->where('is_deleted', 0)
                ->orderBy('session_start_date');
    }

    public function encounterCoursewardOrders()
    {
        return $this->hasMany(EncounterCourseward::class,'batchorder_id')
                ->where('is_deleted', 0);
    }

    public function encounterCoursewardOrder()
    {
        return $this->hasOne(EncounterCourseward::class,'batchorder_id')
                ->where('is_deleted', 0);
    }

    public function referralOrders()
    {
        return $this->hasMany(ReferralOrder::class,'order_batchid')
                    ->where('is_deleted',0)
                    ->where('is_admission',0)
                    ->orderByDesc('create_dt');
    }

    public function deleteLabOrder()
    {
        return $this->diagnosticOrderLabs()->delete();
    }

    public function deleteRadOrder()
    {
        return $this->diagnosticOrderRads()->delete();
    }
}
