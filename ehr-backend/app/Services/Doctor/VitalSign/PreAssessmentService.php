<?php


namespace App\Services\Doctor\VitalSign;
use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\EncounterAssessment;
use App\Models\Mongo\Tracer\TracerEncounterAssessment;
use App\Models\PersonnelCatalog;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\Soap\SoapService;
use DateTime;

class PreAssessmentService
{
    const MONITORING = 'cmp_monitoring_vs';
    const PREASSESSMENT = 'preassessment';

    public $encounter;
    public $assessment;
    public $tracerAssesssment;

    public function __construct(Encounter $encounter)
    {
        $this->_initCon($encounter);
    }

    private function _initCon($encounter)
    {
        $this->encounter = $encounter;
        $this->assessment = empty($encounter->latestEncounterAssessment) ? new EncounterAssessment() : $encounter->latestEncounterAssessment;
        $this->tracerAssesssment = $this->assessment->triageAssessment();
    }

    public static function init($encounter){
        $encounter = Encounter::query()->find($encounter);

        if (empty($encounter))
            throw new EhrException('Encounter was not found. ');

        return new PreAssessmentService($encounter);
    }

    public function getVitalSigns()
    {
        $vs = $this->encounter->triageAssessments;
        $preassessment = [];
        if (!empty($vs)) {
            foreach ($vs as $key => $value) {
                /**
                 * @var EncounterAssessment $value
                 */
                if ($value->form_code === self::PREASSESSMENT) {
                    $tracerInstruction = $value->triageAssessment();
                    $preassessment[] = [
                        'id'            => $value->id,
                        'date'          => str_replace(';', ':', $tracerInstruction->getFieldName('vital_date')),
                        'temperature'   => $tracerInstruction->getFieldName('vital_temperature'),
                        'resp_rate'     => $tracerInstruction->getFieldName('vital_respiratory'),
                        'pulse_rate'    => $tracerInstruction->getFieldName('vital_pulserate'),
                        'diastole'      => $tracerInstruction->getFieldName('vital_diastolic'),
                        'systole'       => $tracerInstruction->getFieldName('vital_systolic'),
                        'o2_saturation' => $tracerInstruction->getFieldName('vital_oxysat'),
                        'modified_by'   => $value->modifiedPersonnel->p->getFullname()
                    ];
                }
            }
        }

        return $this->_reorderVitalList($preassessment);
    }

    private function _reorderVitalList($list){

        $stamps = [];
        foreach ($list as $key => $entry){
            $thisentry = new DateTime($entry['date']);
            $stamps[] = $thisentry->getTimestamp() * 1;
        }

        arsort($stamps);
        $newlist = [];
        foreach ($stamps as $key => $entry){
            $list[$key]['date'] = strtotime($list[$key]['date']);
            $newlist[] = $list[$key];
        }

        return $newlist;
    }

    public static function config(){
        return [
            'm-patient-vital-signs' => [
                'p-vital-signs-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
            ],
        ];
    }

}