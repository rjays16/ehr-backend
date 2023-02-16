<?php

namespace App;

use App\Models\PersonnelCatalog;
use phpDocumentor\Reflection\Types\Integer;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * @property Integer $id
 * @property String $username
 * @property String $password
 * @property String $personnel_id
 * @property String $default_authitem
 * @property Integer $is_active
*/
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'smed_user';

    protected $primaryKey = 'id';
    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, 'personnel_id');
    }
}
