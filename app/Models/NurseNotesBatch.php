<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $encounter_no
 * @property string $is_finalized
 * @property string $nurse_id
 * @property string $create_dt
 * @property string $modify_dt
 * @property string $shift_id
 * */

class NurseNotesBatch extends Model
{

    const IS_FINALIZED = 1;
    public $table = 'smed_nurse_notes_batch';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public $fillable = [
        'id',
        'encounter_no',
        'is_finalized',
        'nurse_id',
        'create_dt',
        'modify_dt',
        'shift_id'
    ];

    //Relations
    public function notes(){
        return $this->hasMany(NurseNotes::class, 'notes_batchid', 'id')
            ->where("flag_document_id", 0);
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, 'nurse_id', 'personnel_id')
            ->select('pid', 'personnel_id');
    }
}