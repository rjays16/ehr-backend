<?php

/**
 * AuxiliaryService.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

use App\Models\AuxiliaryServiceCatalog;

/**
 *
 * Description of AuxiliaryService
 *
 */

class AuxiliaryService implements DiagnosticServiceInterface
{

    /** @var AuxiliaryServiceCatalog $service */
    protected $service;

    /**
     * AuxiliaryService constructor.
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
        return 'AUXILIARY:'.$this->service->service_id;
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
    public function getServicePrice() {
        return $this->service->auxPriceLatest->price;
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
    public function __toString()
    {
        return $this->getName();
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
            'price' => $this->getServicePrice()
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
        $service = AuxiliaryServiceCatalog::model()->findByPk($serviceCode);
        if ($service) {
            return new AuxiliaryService($service);
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
                    'group_code',
                    'group_name'
                ]
            ]
        ];
        $criteria->addCondition('group.is_deleted=0');
        $criteria->order = 'service_name ASC';
        return new CActiveDataProvider(AuxiliaryServiceCatalog::model(), [
            'criteria' => $criteria,
            'pagination' => false
        ]);
    }

}
