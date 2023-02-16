<?php
/**
 * DiagnosticOrderInterface.php
 *
 * @author Jolly Caralos <jadcaralos@gmail.com>
 * @copyright (c) 2017, Segworks Technologies Corporation
 */

namespace App\Models\Order\Diagnostic;

/**
 * Interface DiagnosticOrderInterface
 * @package SegHEIRS\models\order\diagnostic
 */
interface DiagnosticOrderInterface
{
    /**
     * @return string
     */
    public function getOrderDate();

    /**
     * @return string
     */
    public function getRemarks();

    /**
     * @return DiagnosticServiceInterface
     */
    public function getService();
}
