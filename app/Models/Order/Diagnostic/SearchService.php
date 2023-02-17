<?php

/**
 * SearchService.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

/**
 *
 * Description of SearchService
 *
 */
class SearchService
{

    /**
     * @param $query
     * @param array $options
     *
     * @return \CArrayDataProvider
     */
    public function getSearchProvider($query, $options = [])
    {
        $data = [];
        foreach (LaboratoryService::getSearchProvider($query)->getData() as $row) {
            /** @var \LabService $row */
            $data[] = (new LaboratoryService($row))->toArray();
        }

        foreach (BloodBankPackage::getSearchProvider($query)->getData() as $row) {
            /** @var \LabService $row */
            $data[] = (new BloodBankPackage($row))->toArray();
        }

        foreach (RadiologyService::getSearchProvider($query)->getData() as $row) {
            /** @var \RadioService $row */
            $data[] = (new RadiologyService($row))->toArray();
        }

        foreach (SpecialService::getSearchProvider($query)->getData() as $row) {
            /** @var \AuxiliaryServiceCatalog $row */
            $data[] = (new SpecialService($row))->toArray();
        }

        foreach (ServicePackage::getSearchProvider($query)->getData() as $row) {
            /** @var \ServicePackageCatalog $row */
            $data[] = (new ServicePackage($row))->toArray();
        }

        usort($data, function($itemA, $itemB) {
            return strcasecmp($itemA['name'], $itemB['name']);
        });

        return new \CArrayDataProvider($data, $options);

    }

}
