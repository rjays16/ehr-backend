<?php
/**
 * File UpdateTracker.php
 * @author Jan Chris Ogel <iamjc93@gmail.com>
 * @copyright 2020, Segworks Technologies Inc.
 * Date: 10/4/2020
 * Time: 5:40 PM
 */

namespace App\Services\HIS\HisLabTracker;


use App\Models\HIS\HisLabTracker;

class GetRefnoTracker
{
    public static function execute()
    {
        return HisLabTracker::query()
            ->first()
            ->last_refno;
    }
}
