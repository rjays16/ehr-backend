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
use App\Models\HIS\HisUsers;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use App;
use App\Models\Mongo\ConsultationNotification;
use Firebase\JWT\JWT;
use App\Models\Mongo\TelemedUserAccessToken;
use App\Services\Doctor\Permission\PermissionService;
use Carbon\Carbon;
use GuzzleHttp\Client;

class NotificationService
{
    public function getNotifications($userId)
    {
        $er="";
        $ipd="";
        $ipbm="";
        $permission = HisUsers::select('permission')->where('login_id',[$userId])->get();
        $all_access = (strpos($permission,'_a_0_all'));
        $parent_access = ((strpos($permission,'_a_1_notif_lab_dept')) && !(strpos($permission,'_a_2_notif_lab_dept_er') || strpos($permission,'_a_2_notif_lab_dept_ipd') || strpos($permission,'_a_2_notif_lab_dept_ipbm_ipd')));
        if($all_access || $parent_access){
            $er =  "ER";
            $ipd =  "ER_Admission";
            $ipbm =  "IPD_IPBM";
        }else{
            if(strpos($permission,'_a_2_notif_lab_dept_er')){
                $er =  "ER";
            }
            if(strpos($permission,'_a_2_notif_lab_dept_ipd')){
                $ipd =  "ER_Admission";
            }
            if(strpos($permission,'_a_2_notif_lab_dept_ipbm_ipd')){
                $ipbm =  "IPD_IPBM";
            }
        }
        return ConsultationNotification::query()
        ->where('for_mobile', false)
        ->whereIn('data.patient_type',[$er,$ipd,$ipbm])
        ->whereNotIn('seen_emp_usernames',[$userId])
        ->get();
    }

    public function setNotifSeenByRefNo($userId, $refno)
    {
        $notif = ConsultationNotification::query()
            ->where('for_mobile', false)
            ->whereIn('receiver_usernames',[$userId])
            ->whereNotIn('seen_emp_usernames',[$userId])
            ->where('data.refno', $refno)
            ->first();
        if($notif){
            $notif->seen_emp_usernames = array_merge($notif->seen_emp_usernames ? $notif->seen_emp_usernames : [], [$userId]);
            if(!$notif->save()){
                throw new EhrException('Failed to set notification as seen.');
            }
        }
        else{
            throw new EhrException('Notification does not exist.');
        }
    }

    public function setNotifSeen($userId, $notifId)
    {
        $notif = ConsultationNotification::query()
        ->where('for_mobile', false)
        ->whereIn('receiver_usernames',[$userId])
        ->whereNotIn('seen_emp_usernames',[$userId])
        ->find($notifId);
        if($notif){
            $notif->seen_emp_usernames = array_merge($notif->seen_emp_usernames ? $notif->seen_emp_usernames : [], [$userId]);
            if(!$notif->save()){
                throw new EhrException('Failed to set notification as seen.');
            }
        }
        else{
            throw new EhrException('Notification does not exist.');
        }
    }


    public function setNotifSeenAll($userId)
    {
        ConsultationNotification::query()
        ->where('for_mobile', false)
        ->whereIn('receiver_usernames',[$userId])
        ->whereNotIn('seen_emp_usernames',[$userId])
        ->each(function($item) use ($userId){
            $item->seen_emp_usernames = array_merge($item->seen_emp_usernames ? $item->seen_emp_usernames : [], [$userId]);
            if(!$item->save()){
                throw new EhrException('Failed to set notification as seen.');
            }
        });
    }


    public function notifySpmcEmployees(array $receivers)
    {
        $client = new Client();
        $client->post(config('app.NOTIFICATION_URL').'/notification/spmc/user',[
            'form_params' => [
                'receiver' => $receivers
            ],
            'headers' => [
                'Authorization' => 'Bearer '.config('app.NOTIFICATION_TOKEN')
            ]
        ]);
    }
}
