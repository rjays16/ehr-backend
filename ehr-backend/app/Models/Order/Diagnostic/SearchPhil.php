<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 3/20/2019
 * Time: 4:31 AM
 */

namespace App\Models\Order\Diagnostic;


class SearchPhil
{
    public function getSearchProvider($query, $options = [])
    {
        $data = [];
        foreach (PhilMedicineService::getSearchProvider($query)->getData() as $row) {
            $data[] = (new PhilMedicineService($row))->toArray();
        }

        usort($data, function($itemA, $itemB) {
            return strcasecmp($itemA['description'], $itemB['description']);
        });

        return new \CArrayDataProvider($data, $options);
    }
}