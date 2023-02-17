<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/27/2019
 * Time: 12:22 AM
 */

namespace App\Models\Mongo\Tracer;

use App\Models\Mongo\PatientPreAssessment;
use Illuminate\Support\Collection;

class TracerActivity
{
    
    /**
     * @var string $type 
     */
    public $type;


    /**
     * @var Collection
    */
    public $tracer;
    

    /**
     * @var PatientPreAssessment $_tracer
     * @var string $_type
    */
    function __construct($_type, $_tracer)
    {
        $this->type = $_type;
        $this->tracer = collect($_tracer);
    }
}

