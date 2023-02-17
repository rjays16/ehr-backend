<?php


namespace App\Services\Nurse;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\NurseNotes;
use App\Models\NurseNotesBatch;
use App\Models\TracerNurseNotes;
use App\Services\Doctor\PMH\PastMedicalHistoryService;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DARNoteService
{
    public $noteBatch = [];
    public $tracer;

    public function __construct($tracerNotes)
    {
        $this->tracer = TracerNurseNotes::query()->find($tracerNotes);
    }

    public function actionNurseNotes($data)
    {
        $nurseNotesModel = $this->tracer;
        $type = 'old';
        if (empty($this->tracer)) {
            $nurseNotesModel = new TracerNurseNotes();
            $nurseNotesModel->data = $data['data'];
            $nurseNotesModel->action = $data['action'];
            $nurseNotesModel->response = $data['response'];
            $nurseNotesModel->create_dt = date('Y-m-d h:m:s');
            $nurseNotesModel->modify_dt = date('Y-m-d h:m:s');
            $type = 'new';
        }
        $nurseNotesModel->data = $data['data'];
        $nurseNotesModel->action = $data['action'];
        $nurseNotesModel->response = $data['response'];
        $nurseNotesModel->save();

        return [
            'message' => "Nurse Batch Note successfully added!",
            'data' => $nurseNotesModel,
            'type' => $type
        ];
    }
}