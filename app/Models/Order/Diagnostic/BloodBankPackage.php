<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 3/3/2019
 * Time: 9:33 PM
 */

namespace App\Models\Order\Diagnostic;

use App\Models\LabService;

class BloodBankPackage implements DiagnosticServiceInterface
{
    /** @var LabService $service */
    protected $service;

    /**
     * LaboratoryService constructor.
     */
    public function __construct(LabService $service)
    {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'LABORATORY:'.$this->service->service_id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->service->service_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->service->service_name;
    }

    /**
     * @return string
     */
    public function getGroupCode()
    {
        return $this->service->group_id;
    }

    public function getServicePrice()
    {

        return $this->service->labPriceLatest->price;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->service->group->group_name;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->service->group->group_code;
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
        return 'BLOODBANK';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
                'id'          => $this->getIdentifier(),
                'code'        => $this->getCode(),
                'name'        => $this->getName(),
                'groupCode'   => $this->getGroupCode(),
                'groupName'   => $this->getGroupName(),
                'price'       => $this->getServicePrice(),
                'servicename' => $this->getServicename(),
        ];
    }

    /**
     * @param $serviceCode
     *
     * @return static
     * @throws \CException
     */
    public static function instance($serviceCode)
    {
        $service = LabService::model()->findByPk($serviceCode);
        if ($service) {
            return new LaboratoryService($service);
        } else {
            throw new \CException('Laboratory service does not exist');
        }
    }

    /**
     * @param string|null $query
     * @param array       $options
     *
     * @return CActiveDataProvider
     */
    public static function getSearchProvider($query, $options = [])
    {
        $criteria = new CDbCriteria();
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
                                'group_name',
                                'group_alt_name',
                        ],
                ],
        ];
        $criteria->addCondition('group.group_id IN("B")');
        $criteria->addCondition('group.is_deleted=0');
        $criteria->order = 'service_name ASC';

        return new CActiveDataProvider(
                LabService::model(), [
                        'criteria'   => $criteria,
                        'pagination' => false,
                ]
        );
    }
}