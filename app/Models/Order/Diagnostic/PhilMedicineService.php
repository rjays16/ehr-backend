<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 3/20/2019
 * Time: 4:32 AM
 */

namespace App\Models\Order\Diagnostic;

use App\Models\PhilMedicine;

class PhilMedicineService implements DiagnosticServiceInterface
{
    protected $service;

    public function __construct(PhilMedicine $service)
    {
        $this->service = $service;
    }

    public function getIdentifier()
    {
        return $this->service->drug_code;
    }

    public function getCode()
    {
        return $this->service->drug_code;
    }

    public function getName()
    {
        return $this->service->description;
    }

    public function getGroup()
    {
        return $this->service->gen_code;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getGroupName()
    {
        return $this->service->description;
    }

    public function getGroupCode()
    {
        return $this->service->gen_code;
    }

    public function toArray()
    {
        return [
                'id'        => $this->getIdentifier(),
                'code'      => $this->getCode(),
                'name'      => $this->getName(),
                'group'     => $this->getGroup(),
                'groupName' => $this->getGroupName(),
                'groupCode' => $this->getGroupCode(),
        ];
    }

    public static function instance($serviceCode)
    {
        $service = PhilMedicine::model()->findByPk($serviceCode);
        if ($service) {
            return new PhilMedicineService($service);
        } else {
            throw new \CException('Medicine service does not exist');
        }
    }

    public static function getSearchProvider($query, $options = [])
    {
        $criteria = new \CDbCriteria();
        if ($query === '') {
            $criteria->condition = '0';
        } else {
            $criteria->compare('description', $query, true);
        }

        $criteria->order = 'description ASC';

        return new CActiveDataProvider(
                PhilMedicine::model(), [
                        'criteria'   => $criteria,
                        'pagination' => false,
                ]
        );
    }
}