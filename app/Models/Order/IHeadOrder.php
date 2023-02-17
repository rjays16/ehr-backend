<?php
/**
 * IHeadOrder.php
 *
 * @author Jolly Caralos <jadcaralos@gmail.com>
 * @copyright (c) 2017, Segworks Technologies Corporation
 */

namespace App\Models\Order;


/**
 * Interface IHeadOrder
 *
 * @property string  $spin
 * @property integer $is_cash
 * @package SegHEIRS\models\order
 */
interface IHeadOrder
{
    public function getOrderDate();
}