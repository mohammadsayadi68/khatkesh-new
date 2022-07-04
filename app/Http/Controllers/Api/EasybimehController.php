<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Constants\User as UserRole;

class EasybimehController extends Controller
{
    public function getInfo()
    {
        $nationalCode = \request()->get('nationalCode');
        $user = User::whereNationalCode($nationalCode)->firstOrFail();
        switch ($user->role){
            case UserRole::ROLE_EXPERT:
                return response()->json([
                    'FirstName'=>$user->expert->firstname,
                    'LastName'=>$user->expert->lastname,
                    'NationalCode'=>$user->national_code,
                    'Mobile'=>$user->phone,
                    'Phone'=>null,
                    'PostalAddress'=>$user->expert->address,
                    'ZipCode'=>$user->expert->postal_code,
                ]);
            case UserRole::ROLE_LAWYER:
                return response()->json([
                    'FirstName'=>$user->lawyer->firstname,
                    'LastName'=>$user->lawyer->lastname,
                    'NationalCode'=>$user->national_code,
                    'Mobile'=>$user->phone,
                    'Phone'=>null,
                    'PostalAddress'=>$user->lawyer->address,
                    'ZipCode'=>$user->lawyer->postal_code,
                ]);
        }


    }
}
