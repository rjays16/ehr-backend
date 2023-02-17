<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 9/21/2019
 * Time: 1:29 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterCourseward extends Model
{
    protected $table = 'smed_encounter_courseward';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}