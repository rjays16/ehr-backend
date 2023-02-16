<?php

/**
 * DiagnosticServiceFactory.php
 *
 * @author Alvin Quinones <ajmquinones@gmail.com>
 * @copyright (c) 2016, Segworks Technologies Corporation
 *
 */

namespace App\Models\Order\Diagnostic;

/**
 *
 * Description of DiagnosticServiceFactory
 * 
 */

class DiagnosticServiceFactory
{

    /** @var DiagnosticServiceFactory */
    protected static $instance = null;

    /**
     * DiagnosticServiceFactory constructor.
     *
     */
    protected function __construct()
    {
        
    }

    /**
     * @param string $identifier
     *
     * @return DiagnosticServiceInterface
     * @throws \CException
     */
    public function create($identifier)
    {
        list($group, $code) = explode(':', $identifier);
        switch (strtoupper($group)) {
            case 'LAB':
                return LaboratoryService::instance($code);
                break;
        }

        throw new \CException('Invalid diagnostic service identifier');
    }

    /**
     *
     * @return DiagnosticServiceFactory
     */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new DiagnosticServiceFactory();
        }

        return static::$instance;
    }
}
