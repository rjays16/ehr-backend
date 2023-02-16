<?php

namespace App\Models;

use App\Exceptions\EhrException\EhrException;
use App\Models\HIS\HisPersonnel;
use App\Services\Doctor\Permission\PermissionService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property String $personnel_id
 * @property String $pid
 * @property String $license_no
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
 * @property String $tin
*/
class PersonnelCatalog extends Model
{
    protected $table = 'smed_personnel_catalog';

    static $editDiagnosisPermission = '_a_1_doctorseditdiagnosis';

    protected $primaryKey = 'personnel_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    private $permissions_list;

    protected $fillable = [
        'personnel_id',
        'pid',
        'license_no',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
        'tin',
    ];

    public function encounterDoctors(){
        return $this->hasMany(EncounterDoctor::class, 'doctor_id');
    }

    public function personnelAssignment(){
        return $this->hasOne(PersonnelAssignment::class, 'personnel_id')
            ->where(function (Builder $query){
                return $query->where('is_deleted', 0)
                    ->orWhereNull('is_deleted')
                    ->whereBetween("date('".date('Y-m-d')."')",['start_date','end_date'])
                    ->orderByDesc('create_dt');
            });
    }

    public function assignments(){
        return $this->hasMany(PersonnelAssignment::class, 'personnel_id');
    }

    public function getDoctorName()
    {
        
        return $this->getFullNameWithHonorific();
    }

    public function currentAssignment(){
        return $this->hasOne(PersonnelAssignment::class, 'personnel_id')->orderByDesc('create_dt');
    }

    public function p(){
        return $this->belongsTo(PersonCatalog::class, 'pid');
    }


    public function hisPersonnel(){
        return $this->belongsTo(HisPersonnel::class, 'personnel_id','nr');
    }

    public function user(){
        return $this->hasOne(User::class, 'personnel_id')->orderByDesc('user.id');
    }

    public function users(){
        return $this->hasMany(User::class, 'personnel_id');
    }

    public function permissions(){
        return $this->hasMany(PersonnelPermission::class, 'personnel_id','personnel_id')
                    ->where('is_deleted', 0)
                    ->whereHas('permission');
    }

    public function ehrPermissions(){
        return $this->hasMany(PersonnelPermission::class, 'personnel_id','personnel_id')
            ->with('permission')
            ->where('is_deleted', 0)
            ->whereIn('permission_id',PermissionService::permissionConfig())
            ->whereHas('permission');
    }

    public function getFullNameWithHonorific()
    {
        return $this->p->fullname() . " " . $this->honorifics;
    }

    public function hasPermission($permission){
        return in_array($permission, $this->getAllPermission());
    }

    public function getAllPermission(){
        

        if(!is_null($this->permissions_list))
            return $this->permissions_list;

        $pers = PersonnelPermission::query()
                ->where('personnel_id', $this->personnel_id)
                ->where('is_deleted',0)
                ->with('permission')
                ->get();
        $this->permissions_list = [];
        foreach ($pers as $key => $entry){
            if(!is_null($entry->permission))
                $this->permissions_list[] = $entry->permission->permission_id;
        }
        return $this->permissions_list;
    }

    public function checkDoctor(){
        return !$this->currentAssignment->role->where('role_name', 'like', '%Doctor%')->first() ? false : true;
    }

    public  function is_doctor($msg = 'User not authorized.'){
        return $this->checkDoctor() == true ? true : ['status' => false, 'msg' => $msg];
    }

    public function getModifyName($personnel_id){

        $person = self::query()
            ->where("personnel_id", $personnel_id)
            ->with("p")
            ->first();

        $fullname = $person->p->name_last .", ". $person->p->name_first . " " . $person->p->name_middle;
        return $fullname;
    }

    public function concatName($firstName, $middleName, $lastName, $suffix=null){
        return $lastName.', '.$firstName.' '.$middleName.' '.$suffix;
    }

    public function List_Doctors()
    {
        $doctor = DB::table('smed_personnel_catalog as t')
                ->select('t.personnel_id', DB::raw("CONCAT(p.name_last, ', ', p.name_first) AS doctor_name"))
                ->join('smed_person_catalog as p', 't.pid', '=', 'p.pid')
                ->leftJoin('smed_personnel_assignment as pa', 't.personnel_id', '=', 'pa.personnel_id')
                ->where('pa.role_id', '=', 17)
                ->get();
        return $doctor;
    }
}
