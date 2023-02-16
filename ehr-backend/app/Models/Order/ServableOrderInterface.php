<?php

/**
 * ServableOrderInterface.php
 *
 * @author Jolly Caralos <jadcaralos@gmail.com>
 * @copyright Copyright &copy; 2013-2016. Segworks Technologies Corporation
 *
 */

namespace App\Models\Order;

/**
 * Interface for orders that required to be check by refund component
 * if already finalized.
 *
 * @version 1.0
 */
interface ServableOrderInterface extends HospitalOrderInterface
{

    /**
     * [getIsPending description]
     * @return [type] [description]
     */
    public function getIsPending();

    /**
     * [getIsServed description]
     * @return [type] [description]
     */
    public function getIsServed();

    /**
     * [getIsCanceled description]
     * @return [type] [description]
     */
    public function getIsCancelled();

    /**
     * [getIsFinalized description]
     * @return [type] [description]
     */
    public function getIsFinalized();

}