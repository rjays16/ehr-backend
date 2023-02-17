<?php


namespace App\Models\HIS;


use Illuminate\Database\Eloquent\Model;

class HisGuiMgrDetails extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_gui_mgr_details';

    protected $primaryKey = 'nr';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;
}
