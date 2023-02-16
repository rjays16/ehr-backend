<?php


namespace App\Services\Doctor;


use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\AreaCatalog;
use App\Models\Encounter;
use App\Models\ItemCatalog;
use App\Models\MedsOrder;
use App\Models\PatientCatalog;
use App\Models\PersonCatalog;
use App\Models\PersonnelAssignment;
use App\Models\PhilMedicine;
use App\Services\Doctor\Permission\PermissionService;
use App\Utility\JasperReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPJasper\PHPJasper;

class PrescriptionService
{

    /**
     * @var Encounter $encounter
     */
    protected $encounter;

    /**
     * @param Encounter $encObj
     */
    public function __construct($encObj = null)
    {
        $this->encounter = $encObj;    
    }

    /**
     * @param string $encounter_string
     * @return PrescriptionService
     */
    public static function init($encounter_string)
    {
        
        $enc = Encounter::query()->find($encounter_string);
        if(!$enc)
            throw new EhrException('Encounter not found', 404);
        
        return new PrescriptionService($enc);
    }

    /**
     * @param array $selected_orders
     * @param array $order
     * @param int $is_group
     */
    public function prescriptionPDF($selected_orders, $order, $is_group)
    {
        $batch = MedsOrder::query()->find($selected_orders[0])->orderBatch;
        $encounter = $this->encounter;
        // $doctor = EncounterDoctor::model()->getRecentPrimaryDoctor($encounter->encounter_no)->doctor;

        $data = [];

        foreach ($selected_orders as $key => $item) {

            $medsOrder = MedsOrder::query()->find($item);
            if ($medsOrder->is_custom == 1) {
                $meds = $medsOrder->custom_item ? $medsOrder->custom_item : '';
            }else{
                if(!empty($medsOrder->drug_code)){
                    $phil_meds = PhilMedicine::query()->find($medsOrder->drug_code);
                    $meds = $phil_meds->description ? $phil_meds->description : '';
                }else{
                    $item_catalog = ItemCatalog::query()->find($medsOrder->item_id);
                    $meds = $item_catalog->item_name ? $item_catalog->item_name : '';
                }

            }


            $data[] = [
                'encounter' => $medsOrder->orderBatch->encounter_no,
                'sig' => 'Sig: '.$medsOrder->sig,
                'remarks' => 'Remarks: '.$medsOrder->remarks,
                'meds' => $meds,
                'qty' => intval($order[ $item ]['quantity']),
                'date' => date('m/d/Y g:i A',strtotime($medsOrder->order_date)),
                'visit_dt' => $medsOrder->visit_dt == NULL || $medsOrder->visit_dt == '' ? '' : date('m/d/Y g:i A',strtotime($medsOrder->visit_dt)),
                'visit_type' => $medsOrder->visit_type == NULL || $medsOrder->visit_type == '' ? '' : $medsOrder->visit_type,
                'instruction' => $medsOrder->instruction == NULL || $medsOrder->instruction == '' ? '' : $medsOrder->instruction,
                'no' =>  ($key+ 1) .'.'
            ];

        }
        return $this->render($encounter,$data , $batch, $is_group);
    }


    /**
     * @param Encounter $encounter
     * @param     array      $data
     */
    protected function render(Encounter $encounter,$data , $batch, $is_group)
    {
        $report_name = $is_group == '1' ? "/prescription.jrxml" : '/prescription_single.jrxml';
        $doctor = $batch->doctor;
        $assignment = PersonnelAssignment::query()->where('personnel_id',  $doctor->personnel_id)->first();
        $area = AreaCatalog::query()->find($assignment->area_id);
        
        if ($encounter->spin0->p->getFormattedGender() == 'Female') {
            $sex = 'F';
        }
        if ($encounter->spin0->p->getFormattedGender() == 'Male') {
            $sex = 'M';
        }

        $his = HisActiveResource::instance();
        $address = $his->getPersonBasicInformation($encounter->spin0->p->pid) ? $his->getPersonBasicInformation($encounter->spin0->p->pid) : null;

        $street = $address['data']['person_data']['street_name'] ? $address['data']['person_data']['street_name'] : '';
        $barangay = $address['data']['person_data']['brgy_name'] == 'NOT PROVIDED' ? '' : $address['data']['person_data']['brgy_name']. ', ' ;
        $municipality = $address['data']['person_data']['mun_name']  == 'NOT PROVIDED' ? '' : $address['data']['person_data']['mun_name']. ', ' ;
        $province = $address['data']['person_data']['prov_name'] ? $address['data']['person_data']['prov_name']. ', ' : '' ;
        $zipcode = $address['data']['person_data']['zipcode'] ? $address['data']['person_data']['zipcode'] : '' ;
        $complete = $street.', '.$barangay.''.$municipality.''.$province.''.$zipcode;
        
        $params = [

            'logo_path' => getcwd().'/images/mmcis-logo.png',
            'rx_logo' => getcwd().'/images/rx.png',
            'img_footer' => getcwd().'/images/report-footer.png',
            'name' => $encounter->spin0->p->getFullname(),
            'age' => $encounter->spin0->p->getAge(),
            'bod' => date('m.d.Y',strtotime($encounter->spin0->p->birth_date)),
            'sex' => $sex ? $sex : '',
            'address' => preg_replace('/\s\s+/', ' ', $complete),
            'pid' => $encounter->spin,
            'clinic' => $area->area_desc ? $area->area_desc  : '',

            'physician' => $doctor->getDoctorName(),
            'ptr' => $doctor->ptr,
            'license' => $doctor->license_no ? $doctor->license_no : '',
            's2_nr' => $doctor->s2_nr ? $doctor->s2_nr : '',
            'encounter' => $data[0]['encounter'],
            'prescribe_date' => date('m/d/Y',strtotime($data[0]['date'])),
            'visit_dt' => $data[0]['visit_dt'] == NULL || $data[0]['visit_dt'] == '' ? '' : date('m/d/Y', strtotime($data[0]['visit_dt'])),
            'visit_type' => $data[0]['visit_type'] == NULL || $data[0]['visit_type'] == '' ? '' : $data[0]['visit_type'],
            'instruction' => $data[0]['instruction'] == NULL || $data[0]['instruction'] == '' ? '' : $data[0]['instruction'],
        ];
        $jasper = new JasperReport();
        $jasper->showReport("prescription/{$report_name}", $params, $data, 'PDF');
        // $file_name = 'prescription_'.auth()->user()->personnel_id;
        // // $file_name = 'prescription_';
        // $report_path = getcwd() ."/reports/prescription/{$report_name}";
        // $output = getcwd() ."/reports/prescription-output/{$file_name}";
        // Storage::disk('local')->put("prescription/{$file_name}.json", json_encode(['data' => $data]));


        // $options = [
        //     'format' => ['pdf'],
        //     'params' => $params,
        //     'locale' => 'en',
        //     'db_connection' => [
        //         'driver' => 'json',
        //         'data_file' => storage_path()."/app/prescription/{$file_name}.json",
        //         'json_query' => 'data'
        //     ]
        // ];

        // $jasper = new PHPJasper;
        // $jasper->process(
        //     $report_path,
        //     $output,
        //     $options
        // )->execute();

        // return response()->make(file_get_contents($output.".pdf"), 200,[
        //     'Content-type' => "application/pdf",
        //     'Content-disposition' => "inline;filename={$file_name}.pdf",
        //     'Content-Transfer-Encoding' => "binary",
        //     'Accept-Ranges' => "bytes",
        // ]);

        
    }

    /**
     * @return array
     */
    public function defaultOptions(){
        
        return [
            'sigBuilderOptions'              => [
                'method' => [
                        'take'     => 'Take',
                        'apply'    => 'Apply',
                        'chew'     => 'Chew',
                        'insert'   => 'Insert',
                        'inject'   => 'Inject',
                        'spray'    => 'Spray',
                        'inhale'   => 'Inhale',
                        'nebulize' => 'Nebulize',
                        'deliver'  => 'Deliver',
                        'dissolve' => 'Dissolve',
                        'instill'  => 'Instill',
                        'drink'    => 'Drink',
                ],

                'preparation' => [
                        'tablet'       => 'Tablet',
                        'capsule'      => 'Capsule',
                        'mL'           => 'mL',
                        'drop'         => 'Drop',
                        'puff'         => 'Puff',
                        'suppository'  => 'Suppository',
                        'durule'       => 'Durule',
                        'effervescent' => 'Effervescent',
                        'lozenge'      => 'Lozenge',
                        'patch'        => 'Patch',
                        'pill'         => 'Pill',
                        'cream'        => 'Cream',
                        'ointment'     => 'Ointment',
                        'lotion'       => 'Lotion',
                        'gel'          => 'Gel',

                ],

                'frequency' => [
                        'QD'   => 'One Daily',
                        'BID'  => 'Twice Daily',
                        'TID'  => 'Three times daily',
                        'QID'  => 'Four times daily',
                        '5xD'  => 'Five times a day',
                        'Q72h' => 'Every 72 hours',
                        'Q24h' => 'Every 24 hours',
                        'Q12h' => 'Every 12 hours',
                        'Q6H'  => 'Every 6 hours',
                        'Q4H'  => 'Every 4 hours',
                        'Q2H'  => 'Every 2 hours',
                        'QOD'  => 'Every other day',
                        'QAD'  => 'Alternate days',
                        'QW'   => 'Once weekly',
                        'QM'   => 'Once monthly',
                        'QY'   => 'Once yearly',
                ],

                'route' => [
                        'oral'              => 'Orally',
                        'intravenously'     => 'Intravenously',
                        'rectal'            => 'Rectally',
                        'nasal'             => 'Nasally',
                        'topical'           => 'Topically',
                        'Intramuscularly'   => 'Intramuscularly',
                        'intrathecally'     => 'Intrathecally',
                        'subcutaneously'    => 'Subcutaneously',
                        'Sublingually'      => 'Sublingually',
                        'bucally'           => 'Bucally',
                        'vaginally'         => 'Vaginally',
                        'intraocularly '    => 'Intraocularly',
                        'intraotically  '   => 'Intraotically',
                        'inhalation'        => 'Through the mouth (inhalation)',
                        'nebulization'      => 'Through the nose (nebulization)',
                        'cutaneously'       => 'To the skin (cutaneously)',
                        'transdermally'     => 'Through the skin (transdermally)',
                        'intradermally'     => 'Intradermally',
                        'epidurally'        => 'Epidurally',
                        'intracerebrally'   => 'Intracerebrally (into the brain parenchyma)',
                        'intracerebrovent'  => 'Into Cerebral Ventricular',
                        'intraarterially'   => 'Intraarterially',
                        'intra articularly' => 'Into a joint space',
                        'intralesionally'   => 'Intralesionally',
                        'intraperitoneally' => 'Intraperitoneally',
                        'intravesically'    => 'Into the urinary Bladder',
                        'intraosseus'       => 'Via intraosseus infusion',
                ],

                'timing' => [
                        'as_needed'        => 'as needed',
                        'after_meals'      => 'after meals',
                        'before_meals'     => 'before meals',
                        'at_bedtime'       => 'at bedtime',
                        'before_meals_a'   => '30 mins before meals',
                        'before_meals_b'   => '1 hr before meals',
                        'after_meals_a'    => '1 hr after meals',
                        'after_meals_b'    => '2 hrs after meals',
                        'morning'          => 'in the Morning',
                        'lunch'            => 'at lunch',
                        'afternoon'        => 'in the afternoon',
                        'at_night'         => 'at night',
                        'before_breakfast' => 'before breakfast',
                        'after_breakfast'  => 'after breakfast',
                        'before_lunch'     => 'before lunch',
                        'after_lunch'      => 'after lunch',
                        'before_dinner'    => 'before dinner',
                        'after_dinner'     => 'after dinner',
                ],

                'duration_amount' => [
                        '1/4' => 'for 1/4',
                        '1/3' => 'for 1/3',
                        '1/2' => 'for 1/2',
                        '1'   => 'for 1',
                        '1-2' => 'for 1-2',
                        '1-3' => 'for 1-3',
                        '2'   => 'for 2',
                        '2-3' => 'for 2-3',
                        '3'   => 'for 3',
                        '3-4' => 'for 3-4',
                        '4'   => 'for 4',
                        '5'   => 'for 5',
                        '6'   => 'for 6',
                        '7'   => 'for 7',
                        '8'   => 'for 8',
                        '9'   => 'for 9',
                ],

                'duration_interval' => [
                        'd' => 'day/s',
                        'w' => 'week/s',
                        'm' => 'month/s',
                ],

        ]
        ];
    }


    public static function config()
    {
        return [
                'm-patient-prescription' => [
                    'prescription-view' => [
                        'role_name' => [
                            PermissionService::$doctor,
                            PermissionService::$nurse
                        ],
                        'other-permissions' => []
                    ],
                    'prescription-save' => [
                        'role_name' => [
                            PermissionService::$doctor
                        ],
                        'other-permissions' => []
                    ],
                    'default-options' => [
                        'prescription' => (new PrescriptionService())->defaultOptions()
                    ]
                ]
        ];
    }



}