<?php
/**
 * File UpdateRefnoLabOrder.php
 * @author Jan Chris Ogel <iamjc93@gmail.com>
 * @copyright 2020, Segworks Technologies Inc.
 * Date: 10/26/2020
 * Time: 6:25 PM
 */

namespace App\Services\Diagnostic;


use App\Models\DiagnosticOrderLab;

class UpdateRefnoLabOrder
{
    public static function execute($batch_id, $refno)
    {
        return DiagnosticOrderLab::query()
            ->where('order_batchid', '=', $batch_id)
            ->update(['refno' => $refno]);
    }
}
