<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 3/20/2019
 * Time: 4:24 AM
 */

namespace App\Models\Order\Diagnostic;


class PhilMedicineFactory
{

    protected static $instance = null;

    protected function __construct()
    {

    }

    public function create($identifier)
    {
        list($code) = explode(':', $identifier);
        return PhilMedicineService::instance($code);

    }

    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new PhilMedicineFactory();
        }

        return static::$instance;
    }

}