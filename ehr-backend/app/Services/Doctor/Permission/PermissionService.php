<?php


namespace App\Services\Doctor\Permission;

use App\Exceptions\EhrException\EhrException;
use App\Models\Config;
use App\Models\Encounter;
use App\Models\PermissionCatalog;
use App\Services\Patient\PatientService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PDO;

class PermissionService
{
    public static $doctor = 'doctor';
    public static $nurse = 'nurse';
    public static $allPermissions;
    public static $ehrPermissions;

    public static $errorMessage = "You don't have permission.";
    public static $errorCode = 5011;


    /**
     * @var bool $inMyDept
     */
    private $inMyDept;

    
    
    /**
     * @var Encounter $encounter
     */
    private static $encounter;

    /**
     * @var User $user;
     */
    private static $user;
    
    
    public function __construct($encounter = null)
    {

        if(!is_null($encounter)){
          if(is_null(self::$user))
              self::$user = auth()->user();

          if(self::$encounter){
              if(self::$encounter->encounter_no != $encounter->encounter_no){
                  self::$encounter = $encounter;
                  self::$inMyDept = null;

                  $this->evaluatePatient();
              }
          }
          else{
              self::$encounter = $encounter;
              $this->evaluatePatient();
          }
        }
    }

    /**
     * check if patient belong to users department
     */
    public function evaluatePatient()
    {
        if(is_null(self::$encounter))
          return $this->inMyDept = false;
        else if(!count(self::$encounter->toArray()))
          return $this->inMyDept = false;
        
        if(PatientService::getEncounters(PatientService::$filterByEnc, self::$encounter->encounter_no)->fetch(PDO::FETCH_ASSOC))
            $this->inMyDept = true;
        else
            $this->inMyDept = false;

    }


    /**
     * @var array $ids
     * @return Collection
     */
    public static function getAllPermissions(array $ids = [])
    {
        return PermissionCatalog::query()->select(['id','permission_id'])->whereIn('id', $ids)->get();
    }


    public function isInMyDept():bool
    {
        return $this->inMyDept;
    }


    public static function permissionWithViewAll(){
        return [
            Permisssion::manageEhr(),
            Permisssion::viewAllDept(),
            Permisssion::editAllDept() 
        ];
    }


    public static function permissionWithView(){
        return [
            Permisssion::manageEhr(),
            Permisssion::viewAllDept(),
            Permisssion::editAllDept(),
            Permisssion::viewOwnDept(),
            Permisssion::editOwnDept()  
        ];
    }

    public static function permissionConfig():array
    {
        return [
            Permisssion::manageEhr(),
            Permisssion::viewAllDept(),
            Permisssion::viewOwnDept(),
            Permisssion::editAllDept(),
            Permisssion::editOwnDept(),
            Permisssion::overideInctSoapDiag(),
            Permisssion::overideInct(),
            Permisssion::overideInctSoap(),
            Permisssion::overideInctSoapDiag2(),
            Permisssion::overideInctPastMed(),
            Permisssion::overideInctPsigns(),
            Permisssion::overideInctHciRef(),
            Permisssion::overideInctPhysExam(),
            Permisssion::overideInctMedicat(),
            Permisssion::overideInctPlanMan(),
            Permisssion::overideInctEndCare()
        ];
    }


    /**
     * @var array $ids
     * @return Collection
     */
    public static function getAllEhrPermissions():Collection
    {
        if(is_null(self::$ehrPermissions))
            self::$ehrPermissions = auth()->user()->personnel->ehrPermissions->map(function($perm){
                return $perm->permission;
            });
        return self::$ehrPermissions;
    }

    public function hasManageEhr():bool{
        return self::getAllEhrPermissions()->where('id' , Permisssion::manageEhr())->first() !== null;
    }

    public function hasEdit():bool{
        if(self::getAllEhrPermissions()->where('id' , Permisssion::editAllDept())->first())
            return true;

        return self::getAllEhrPermissions()->where('id' , Permisssion::editOwnDept())->first() !== null && $this->isInMyDept();
    }

    public function hasView():bool{
        if(self::getAllEhrPermissions()->where('id' , Permisssion::viewAllDept())->first())
            return true;
            
        return self::getAllEhrPermissions()->where('id' , Permisssion::viewOwnDept())->first() !== null && $this->isInMyDept();
    }

    public function hasHigherPermission():bool{
        return $this->hasManageEhr();
    }

    public function hasOverInct():bool{
        return self::getAllEhrPermissions()->where('id' , Permisssion::overideInct())->first() !== null;
    }

    public function hasDischPerm(string $type):bool{
        self::$errorMessage = "Patient already discharged.";
        
        if(!($this->hasView() || $this->hasEdit()))
          return false;
        
        if($this->hasOverInct())
          return true;

        switch($type){
          case PermisssionPortlet::$soap:
              return  self::getAllEhrPermissions()->where('id' , Permisssion::overideInctSoap())->first() !== null;
          case PermisssionPortlet::$soapDiag:
              return self::getAllEhrPermissions()->whereIn('id' , [
                  Permisssion::overideInctSoapDiag(),
                  Permisssion::overideInctSoapDiag2()
              ])->first() !== null;
          case PermisssionPortlet::$pastMed:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctPastMed())->first() !== null;
          case PermisssionPortlet::$pSigns:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctPsigns())->first() !== null;
          case PermisssionPortlet::$refHci:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctHciRef())->first() !== null;
          case PermisssionPortlet::$physExam:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctPhysExam())->first() !== null;
          case PermisssionPortlet::$medict:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctMedicat())->first() !== null;
          case PermisssionPortlet::$planMan:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctPlanMan())->first() !== null;
          case PermisssionPortlet::$endCare:
              return self::getAllEhrPermissions()->where('id' , Permisssion::overideInctEndCare())->first() !== null;
        }

          

        return false;
    }

  public function isDischarged():bool{
    if(self::$encounter)
      return self::$encounter->is_discharged == 1;
    return false;
  }

  public function hasSoapEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$soap);
    return $this->hasEdit();
  }

  public function hasSoapDiagEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$soapDiag);
    return $this->hasEdit();
  }

  public function hasSoapView():bool{
    if($this->hasSoapEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }
  
  
  public function hasPastMedEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$pastMed);
    return $this->hasEdit();
  }

  public function hasPastView():bool{
    if($this->hasPastMedEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }

  public function hasPSignsEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$pSigns);
    return $this->hasEdit();
  }

  public function hasPSingsView():bool{
    if($this->hasPSignsEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }

  public function hasVitalSignsView():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->hasView()||$this->hasEdit() || $this->hasEdit())
      return true;
    return false;
  }

  public function hasRefHciEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$refHci);
    return $this->hasEdit();
  }

  public function hasRefHciView():bool{
    if($this->hasRefHciEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }


  public function hasPhysExamEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$physExam);
    return $this->hasEdit();
  }

  public function hasPhysExamView():bool{
    if($this->hasPhysExamEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }


  public function hasResultView():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->hasView()||$this->hasEdit() || $this->hasEdit())
      return true;
    return false;
  }

  public function hasDrugsMedsEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$medict);
    return $this->hasEdit();
  }

  public function hasDrugsMedsView():bool{
    if($this->hasDrugsMedsEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }


  public function hasEncHistView():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->hasView()||$this->hasEdit() || $this->hasEdit())
      return true;
    return false;
  }


  public function hasReferalsView():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->hasView()||$this->hasEdit() || $this->hasEdit())
      return true;
    return false;
  }

  public function hasEndCareEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$endCare);
    return $this->hasEdit();
  }

  public function hasEndCareView():bool{
    if($this->hasEndCareEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }


  public function hasPlanManEdit():bool{
    if($this->hasHigherPermission())
      return true;
    else if($this->isDischarged())
      return $this->hasDischPerm(PermisssionPortlet::$planMan);
    return $this->hasEdit();
  }

  public function hasPlanManView():bool{
    if($this->hasPlanManEdit() || $this->hasView() || $this->hasEdit())
      return true;
    return false;
  }
}

class Permisssion{
    public static function manageEhr() { return config('app.manageEhr'); }
    public static function viewAllDept() { return config('app.viewAllDept'); }
    public static function viewOwnDept() { return config('app.viewOwnDept'); }
    public static function editAllDept() { return config('app.editAllDept'); }
    public static function editOwnDept() { return config('app.editOwnDept'); }
    public static function overideInctSoapDiag() { return config('app.overideInctSoapDiag'); }
    public static function overideInct() { return config('app.overideInct'); }
    public static function overideInctSoap() { return config('app.overideInctSoap'); }
    public static function overideInctSoapDiag2() { return config('app.overideInctSoapDiag2'); }
    public static function overideInctPastMed() { return config('app.overideInctPastMed'); }
    public static function overideInctPsigns() { return config('app.overideInctPsigns'); }
    public static function overideInctHciRef() { return config('app.overideInctHciRef'); }
    public static function overideInctPhysExam() { return config('app.overideInctPhysExam'); }
    public static function overideInctMedicat() { return config('app.overideInctMedicat'); }
    public static function overideInctPlanMan() { return config('app.overideInctPlanMan'); }
    public static function overideInctEndCare() { return config('app.overideInctEndCare'); }
}


class PermisssionPortlet{
    public static $soap = 'soap';
    public static $soapDiag = 'soapDiag';
    public static $pastMed = 'pastMed';
    public static $pSigns = 'pSings';
    public static $refHci = 'refHci';
    public static $physExam = 'physExam';
    public static $medict = 'medict';
    public static $planMan = 'planMan';
    public static $endCare = 'endCare';
}