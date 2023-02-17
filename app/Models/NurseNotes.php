<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

/**
 * @property string $id
 * @property string $notes_batchid
 * @property string $document_id
 * @property string $flag_document_id
 * @property string $nurse_id
 * @property string $create_dt
 */
class NurseNotes extends Model
{
    public $table = 'smed_nurse_notes';
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;
    use HybridRelations;

    public $fillable = [
        'id',
        'notes_batchid',
        'document_id',
        'flag_document_id',
        'nurse_id',
        'create_dt'
    ];

    //Relationships
    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, 'nurse_id', 'personnel_id')
            ->select('pid', 'personnel_id');
    }

    public function darnotes(){
        return $this->hasOne(TracerNurseNotes::class, '_id', 'document_id');
    }


    //Queries
    public function getNotes($batch_id){
        $query = self::query()
            ->where("notes_batchid", $batch_id)
            ->get();

        $notes = [];
        foreach ($query as $key => $entry){
            $notes[] = [
                'id' => $entry->nr,
                'notes_batchid' => $entry->ward_id,
                'document_id' => $entry->name,
                'nurse_id' => $entry->description,
                'create_dt' => $entry->description
            ];
        }
        return $notes;
    }
}