<?php

/**
 * DiagnosticOrderAdapter.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

/**
 *
 * Description of DiagnosticOrderAdapter
 *
 */

interface DiagnosticServiceInterface
{

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getGroupCode();

    /**
     * @return string
     */
    public function getGroupName();

    /**
     * @return string
     */
    public function getGroup();
    /**
     * @return string
     */
    public function __toString();

    /**
     * @return array
     */
    public function toArray();
}
