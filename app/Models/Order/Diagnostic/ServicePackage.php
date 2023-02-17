<?php

/**
 * ServicePackage.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

use App\Exceptions\EhrException\EhrException;
use App\Models\ServicePackageCatalog;

/**
 *
 * Description of ServicePackage
 *
 */

class ServicePackage implements DiagnosticServiceInterface
{

    /** @var ServicePackageCatalog $service */
    private $service;

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
        return 'PACKAGE:'.$this->service->package_code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->service->package_code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->service->package_name;
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
    public function getServicePrice()
    {
        return is_null($this->service->recentPrice)? 0 : $this->service->recentPrice->price;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return is_null($this->service->group)? 0 : $this->service->group->group_name;
    }


    /**
     * @return string
     */
    public function getGroup()
    {
        return is_null($this->service->group)? 0 : $this->service->group->group_code;
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
     * @throws EhrException
     */
    public static function instance($serviceCode)
    {
        $service = ServicePackageCatalog::query()->find($serviceCode);
        if ($service) {
            return new ServicePackage($service);
        } else {
            throw new EhrException('Service package does not exist', 404);
        }
    }

    /**
     * @param string|null $query
     * @param array $options
     *
     * @return CActiveDataProvider
     */
    public static function getSearchProvider($query, $options = [])
    {
        $criteria = new CDbCriteria();
        if ($query === '') {
            $criteria->condition = '0';
        } else {
            $criteria->compare('package_name', $query, true);
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
        $criteria->order = 'package_name ASC';
        return new CActiveDataProvider(ServicePackageCatalog::model(), [
            'criteria' => $criteria,
            'pagination' => false
        ]);
    }

}
