<?php

namespace App\Http\Controllers\Api;

use App\AppAction;
use App\Http\Resources\AppActionCollection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppActionController extends Controller
{
    public function index()
    {

        $appActions = \Cache::remember('appActions',1111111,function (){
            return AppAction::whereShowType(1)->orderBy('order')->get();
        });
        return new AppActionCollection($appActions);
    }

    public function teammateList()
    {
        $appActions = AppAction::whereShowType(2)->orderBy('order')->get();
        return new AppActionCollection($appActions);
    }
}
