<?php

/**
 * RadiologyService.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

use App\Models\AreaCatalog;
use App\Models\RadioService;
use App\Models\RadioServiceGroup;
use App\Models\TransactionType;
use App\Services\Doctor\Permission\PermissionService;
use App\Models\Encounter;
use App\Models\PhilFrequency;
use App\Services\HIS\HisPhicCoverage\HisDiscountService;

/**
 *
 * Description of RadiologyService
 *
 */

class RadiologyService implements DiagnosticServiceInterface
{

    /** @var RadioService $service */
    protected $service;

    /**
     * RadiologyService constructor.
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'RADIOLOGY:'.$this->service['service_id'];
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->service['service_id'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->service['service_name'];
    }

    /**
     * @return string
     */
    public function getGroupCode()
    {
        return $this->service['group_id'];
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return is_null($this->service['group'])? 0 : $this->service['group']['group_code'];
    }

    /**
     * @return string
     */
    public function getServicePrice() {
        return is_null($this->service->latestRadpriceCatalog)? 0 : $this->service->latestRadpriceCatalog->price;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->service['group_name'] ? $this->service['group_name'] : $this->service['group']['group_name'];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    public function getServicename()
    {
        return 'RADIOLOGY';
    }

    public function getSocialized()
    {
        return $this->service['is_socialized'];
    }

    public function getStatus()
    {
        $status = '';
        if(!empty($this->service['status'])){
            if(!empty($this->service['is_restricted'])){
                $status = 'Restricted';
            }else{
                $status = 'Unavailable';
            }
        }else{
            if(!empty($this->service['is_restricted'])){
                $status = 'Restricted';
            }else{
                $status = '';
            }
        }
        return $status;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getIdentifier(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'groupCode' => $this->getGroupCode(),
            'groupName' => $this->getGroupName(),
            'servicename' => $this->getServicename(),
            'is_socialized' => $this->getSocialized(),
            'status'      => $this->getStatus(),
            'cash_price' => $this->getCash(),
            'charge_price' => $this->getCharge(),
            'net_price'  => $this->getNetPrice(),
        ];
    }

    public function getCash()
    {
        return $this->service['price_cash'];
    }

    public function getCharge()
    {
        return $this->service['price_charge'];
    }

    public function getNetPrice()
    {
        return $this->service['net_price'] ? : 0;
    }

    /**
     * @param $serviceCode
     *
     * @return static
     * @throws \CException
     */
    public static function instance($serviceCode)
    {
        $service = RadioService::model()->findByPk($serviceCode);
        if ($service) {
            return new RadiologyService($service);
        } else {
            throw new \CException('Laboratory service does not exist');
        }
    }

    /**
     * @param string|null $query
     * @param array $options
     *
     * @return CActiveDataProvider
     */
    public static function getSearchProvider($query, $options=[])
    {
        $criteria = new \CDbCriteria();
        if ($query === '') {
            $criteria->condition = '0';
        } else {
            $criteria->compare('service_name', $query, true);
        }
        $criteria->addCondition('t.is_deleted=0');
        $criteria->with = [
            'group' => [
                'select' => [
                    'group_id',
                    'area_id',
                    'group_name'
                ]
            ]
        ];
        $criteria->addCondition('group.is_deleted=0');
        $criteria->order = 'service_name ASC';
        return new CActiveDataProvider(RadioService::model(), [
            'criteria' => $criteria,
            'pagination' => false
        ]);
    }

    public static function config($encounter_no = null)
    {
        return [
            'm-patient-radiology' => [
                    'p-patient-radiology-view' => [
                            'role_name'         => [],
                            'other-permissions' => [],
                    ],
                    'p-patient-radiology-save' => [
                            'role_name'         => [
                                PermissionService::$doctor,
                            ],
                            'other-permissions' => [],
                    ],
                    'default-options' => collect([])
                        ->merge([
                            'rad-group-areas' => self::getArea(),
                        ])
                        ->merge([
                            'transaction_type' => self::getChargeType(),
                            'monitoring_option' => self::getMonitoring(),
                        ])
                        ->merge(self::getDiagnosticRadServiceOptions($encounter_no))
                        ->merge([])
                    ,
            ],
        ];
    }

    private static function getDiagnosticRadServiceOptions($encounter_no = null)
    {
	    $encounter = Encounter::where('encounter_no', $encounter_no)->first();

        $rad = self::getRadServices($encounter);

        $RadGroups = RadioServiceGroup::query()->whereNotIn('group_id',["B", "SPL", "SPC", "CATH", "ECHO"])->get()->map(function ($group) {
            /** @var RadioServiceGroup $group */
            return [
                'id'   => $group->group_id,
                'area_id'   => $group->area_id,
                'name' => $group->group_name,
            ];
        });

        return [
            'rad-services' => $rad,
            'rad-group-services' => $RadGroups,
        ];
    }

    public static function getRadServices(Encounter $encounter, array $service_ids = [])
    {
        $discountService = new HisDiscountService($encounter);
        $discountDetails = $discountService->execute();
        $service = $discountService->computeNetPriceRad($discountDetails['discountid'], $discountDetails['discount'], $service_ids);

        return collect($service)->map(function ($service) {
            /** @var RadService $service */

            $rad = new RadiologyService($service);
            return $rad->toArray();
        });
    }

    public static function getChargeType()
    {
        $model = new TransactionType();
        $transaction = $model->TransactionType();
        $menu = [
            [
                'id' => '',
                'charge_name' => 'PERSONAL',
            ]
        ];
        foreach ($transaction as $key => $entry) {
            $menu[] = [
                'id' => $entry['id'],
                'charge_name' => $entry['charge_name']
            ];
        }

        return $menu;
    }

    public static function getMonitoring(){
        $freqs = PhilFrequency::where('is_diagnostic', '=', '0')->get();

        $monitoring_menu = [];
        foreach ($freqs as $key => $freq) {
            $monitoring_menu[] = [
                'frequency_code' => $freq['frequency_code'],
                'frequency_desc' => $freq['frequency_disc'],
            ];
        }

       return $monitoring_menu;
    }

    private static function getArea()
    {
        return AreaCatalog::query()
                    ->whereIn('dept_id',['158'])
                    ->orderBy('area_desc')->get()
                    ->map(function (AreaCatalog $area) {
                        return [
                                'id'   => $area->area_id,
                                'name' => $area->area_desc,
                        ];
                    })
                    ;
    }

}
