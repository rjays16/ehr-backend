<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 5:51 PM
 */

namespace App\Services\User;

use App\Exceptions\EhrException\EhrException;
use App\Models\Mongo\UserAccessToken;
use App\Models\PersonCatalog;
use App\Models\PersonnelCatalog;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use App;
use Firebase\JWT\JWT;
use App\Models\Mongo\TelemedUserAccessToken;
use App\Services\Doctor\Permission\PermissionService;
use Carbon\Carbon;

class UserService
{
    public function createPersonnel($params)
    {
        $this->createPersonnel1($params);
    }

    public function createPersonnel1($params)
    {
        $personel = $this->_is_personnel_exist($params['nr']);
        if ( ! $personel) {
            $personel = new PersonnelCatalog();
            $personel->personnel_id = $params['nr'];
        }
        $personel->tin = $params['tin'];
        $personel->pid = $params['pid'];
        if ( ! ($personel->save())) {
            throw new EhrException('Unable to save personnel.', 500,
                ['data' => $params], true);
        }
    }

    private function _is_personnel_exist($personnel)
    {
        $personel = PersonnelCatalog::query()->find($personnel);

        return is_null($personel) ? false : $personel;
    }


    public function createUser($params)
    {
        $model = User::query()->where('personnel_id', $params['nr'])->first();

        if (empty($model)) {
            $model = new User();
        }

        $model->username = $params['username'];
        $model->password = $params['password'];
        $model->default_authitem = 'doctor'; //$params['default_authitem']
        $model->personnel_id = $params['nr'];

        if ( ! ($model->save())) {
            throw new EhrException('', 500,
                ['model_err' => $model->getErrors(), 'user_data' => $params],
                true);
        }

    }

    public function savePerson($params)
    {

        $person = $this->_isPersonExist($params['pid']);

        if ($person === false) {
            $person = new PersonCatalog();
            $person->pid = $params['pid'];
        }


        $person->name_last = $params['lastName'];
        $person->name_first = $params['firstName'];
        $person->name_middle = $params['middleName'];
        $person->suffix = $params['suffix'];
        $person->gender = $params['gender'];
        $person->contact_nos = $params['cellphone_1_nr'];
        $person->create_id = $params['create_id'];
        $person->birth_date = $params['dateOfBirth'];
        $person->birth_place = $params['place_birth'];
        $person->personSearch = $params['name_search'];
        $person->nationality_id = 1;
        $person->email = $params['email'];
        $person->soundex_name_first = $params['soundex_namefirst'];
        $person->soundex_name_last = $params['soundex_namelast'];
        $person->create_id = $params['create_id'];
        $person->create_dt = isset($params['create_dt']) ? $params['create_dt']
            : date('Y-m-d H:i:s');
        $person->modify_dt = isset($params['modify_dt']) ? $params['modify_dt']
            : date('Y-m-d H:i:s');

//            $person->soundex_name_middle = $params['soundex_namemiddle'];
//            $person->classification_id = $params['pid'];
//            $person->is_deleted = $params['pid'];
//            $person->prov_code = $params['zipcode'];
//            $person->religion_id = $params['religion'];
//            $person->civil_status = $params['civil_status'];
//            $person->nationality = $params['citizenship'];
//            $person->brgy_code = $params['barangay'];
//            $person->lgu_code = $params['city'];

        if ( ! $person->save()) {
            throw new EhrException('Unable to save Person Catalog', 500, [
                'model_er' => $person->getErrors(), 'params_person' => $params,
            ], true);
        }

    }

    private function _isPersonExist($pid)
    {
        $person = PersonCatalog::query()->find($pid);

        return $person ? $person : false;
    }


    /**
     * @return mixed|string token
     */
    private function _login(
        $username,
        $password,
        $device_uuid = '',
        $device_device_unique_id = '',
        $device_platform = '',
        $device_model = '',
        $isMobile = false
    ) {
        if ( ! $token = auth()->attempt([
            'username' => $username,
            'password' => $password,
        ])
        ) {
            throw new EhrException('Incorrect username or password.', 401);
        }



        $user = auth()->user();
        $person = $user->personnel->p;

        if(is_null($user->personnel->hisPersonnel))
            throw new EhrException('Personnel does not exist on HIS.', 401);

        $level = $user->personnel->hisPersonnel->doctorLevel;
        $colle = collect($person);
        $colle->forget([
            'address_line2', 'brgy_code', 'lgu_code', 'prov_code',
            'foreign_address', 'religion_id', 'civil_status_id',
            'classification_id', 'id_no', 'id_code', 'create_id', 'create_dt',
            'nationality_id', 'soundex_name_last', 'soundex_name_first',
            'soundex_name_middle', 'modify_id', 'modify_dt', 'occupation_id',
            'occupation_specific', 'ethnic_id', 'bloodtype_id', 'brgy_name',
        ]);
        $assignment = $user->personnel->currentAssignment;
        $role = $assignment->role;


        if ($role->is_allowed === '0') {
            throw new EhrException('You do not have permission to use the System. Contact the ADMIN.', 401);
        }
        elseif ($user->is_active==0 || $user->username != $username) {
            throw new EhrException('Incorrect username or password.', 401);
        }


        if ($isMobile) {

            if(PermissionService::getAllEhrPermissions()->first() == null)
                throw new EhrException(PermissionService::$errorMessage);

            $token = $this->generateToken([
                'user_id' => $user->username,
            ]);

            $data = UserAccessToken::saveToken($token, $device_uuid,
                $device_device_unique_id, $device_platform, $device_model,
                $user->id);
            if ( ! $data) {
                throw new EhrException("Can't save new access key.", 401);
            }
        }

        return [
            'token' => $token,
            'user'  => [
                'id'                 => $user->id,
                'username'           => $user->username,
                'personnel_id'       => $user->personnel_id,
                'is_active'          => $user->is_active,
                'person'             => $colle,
                'current_assignment' => [
                    'assignment_id' => $assignment->id,
                    'area_id'       => $assignment->area_id,
                    'dept_id'       => $assignment->dept_id,
                ],
                'role'               => [
                    "role_id"    => $role->role_id,
                    "role_name"  => $role->role_name,
                    "role_desc"  => $role->role_desc,
                    "role_area"  => $role->role_area,
                    "is_allowed" => $role->is_allowed,
                ],
                'permissions'        => PermissionService::getAllEhrPermissions(),
                'level' => $level,
            ],
            'isTestServer' => config('app.env') != 'production'
        ];

    }


    /**
     * @return array
     * */
    public function loginMobileApi(
        $username,
        $password,
        $device_uuid,
        $device_device_unique_id,
        $device_platform,
        $device_model
    ) {
        return $this->_login($username, $password, $device_uuid,
            $device_device_unique_id, $device_platform, $device_model, true);
    }

    /**
     * @return array
     * */
    public function loginWebApi($username, $password)
    {
        return $this->_login($username, $password);
    }


    /**
     * @return string
     * */
    public function logout()
    {
        $auth = App::make(JWTAuth::class);
        $token = $auth->getToken();

        $resp = UserAccessToken::removeAccess($token->get());
        if ($resp) {
            $auth->parseToken()->invalidate();

            return 'You have successfully logged out.';
        } else {
            throw new EhrException('User failed to logout. Access key does not exist.');
        }

    }

    public function telemedAuthenticateToken($token)
    {
        $resp = UserAccessToken::token($token);
        if ( ! $resp) {
            throw new EhrException('Acces token is invalid.', 401);
        }
    }


    public function authenticateToken($token, $userid = '')
    {

        $resp = UserAccessToken::token($token, $userid);

        if ( ! $resp) {
            throw new EhrException('Acces token is invalid.', 401);
        }

        $user = User::query()->where('id', $resp->user_id)->where('is_active',1)->first();
        $this->setUser($user);
    }

    public function setAuthUser($username)
    {
        $user = User::query()->where('username', $username)->where('is_active',1)->first();

        $this->setUser($user);
    }

    private function setUser(User $user)
    {
        if ( ! $user) {
            throw new EhrException('User does not exist.');
        }

        auth()->login($user);
        auth()->setUser($user);
    }


    public function generateToken($payload, $expirationTimestamp = null)
    {
        $now = Carbon::now();
        $issuedAt = $notBefore = $now->timestamp;

        if(is_null($expirationTimestamp)){
            $expiresIn = $now->addMonths(1)->timestamp; // set 1 month expiration for tokens
        }
        else{
            $expiresIn = $expirationTimestamp;
        }

        return JWT::encode([
            'iat'		=> $issuedAt,
            'jti'		=> 1,
            'iss'		=> config('app.url'),
            'nbf'		=> $notBefore,
            'exp'		=> $expiresIn,
            'dta'		=> $payload
        ],config('jwt.secret'),config('jwt.algo'));
    }
}
