<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:28 PM
 */

namespace App\Services;

use App\Models\Mongo\SpmcOneSignalPlayer;

class TelemedService
{
    public function registerOneSignal($playerId, $appName)
    {
        if($playerId && $appName){
            $player = SpmcOneSignalPlayer::query()->where('player_id', $playerId)->where('onesignal_app_name', $appName);
            if($player->first())
                $player->update([
                    'user_id' => auth()->user()->username,
                    'is_telemed' => false,
                ]);
            else
                SpmcOneSignalPlayer::query()->create([
                    'player_id' => $playerId,
                    'user_id' => auth()->user()->username,
                    'is_telemed' => false,
                    'onesignal_app_name' => $appName
                ]);
        }
    }

}