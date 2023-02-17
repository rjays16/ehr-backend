<?php

namespace App\Services\Doctor;

use Illuminate\Support\Facades\Storage;

class FileService
{
    public static function write($data, $fileExtension = 'pdf', $fileName = null):String
    {
        if(is_null($fileName)){
            $user = md5(auth()->user()->personnel_id);
            for ($i=0; $i < 10; $i++) { 
                $fileName = $user."_".$i;
                if(Storage::disk('reports')->exists("temp/c_{$fileName}.{$fileExtension}")){
                    Storage::disk('reports')->delete("temp/c_{$fileName}.{$fileExtension}");
                }
            }
    
            $fileName = $user."_".rand(0,10);
        }

        Storage::disk('reports')->put("temp/c_{$fileName}.{$fileExtension}", $data);
        return "reports/temp/c_{$fileName}.{$fileExtension}";
    }


}