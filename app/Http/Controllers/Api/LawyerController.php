<?php

namespace App\Http\Controllers\Api;

use App\Expert;
use App\Expertise;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpertResource;
use App\Http\Resources\LawyerResource;
use App\Http\Resources\LawyersCollection;
use App\Lawyer;
use App\LawyerGrade;
use App\Province;
use Illuminate\Http\Request;

class LawyerController extends Controller
{
    public function lawyers()
    {
        $this->validate(request(), [
            'fullname' => 'nullable|string',
            'minExpireDate' => 'nullable|date_format:Y/m/d',
            'maxExpireDate' => 'nullable|date_format:Y/m/d',
            'provinceId' => 'nullable|numeric',
            'cityId' => 'nullable|numeric',
            'gradeId' => 'nullable|numeric'
        ]);
        $fullname = request()->get('fullname');
        $minExpireDate = request()->get('minExpireDate');
        $maxExpireDate = request()->get('maxExpireDate');
        $provinceId = request()->get('provinceId');
        $cityId = request()->get('cityId');
        $gradeId = request()->get('gradeId');


        $experts = Lawyer::with('user');
        if ($fullname) {
            $experts = $experts
                ->leftJoin('users', 'users.id', '=', 'lawyers.user_id')
                ->where('users.name', 'LIKE', "%$fullname%");
        }
        if ($minExpireDate) {
            $minExpireDate = toGregorian($minExpireDate);
            $experts = $experts->whereDate('lawyers.expire_date', '>=', $minExpireDate);
        }
        if ($maxExpireDate) {
            $maxExpireDate = toGregorian($maxExpireDate);
            $experts = $experts->whereDate('lawyers.expire_date', '<=', $maxExpireDate);
        }
        if ($provinceId) {
            $experts = $experts->where('lawyers.province_id', $provinceId);
        }
        if ($cityId) {
            $experts = $experts->where('lawyers.city_id', $cityId);
        }
        if ($gradeId) {
            $experts = $experts->where('lawyers.grade_id', $gradeId);
        }
        $lawyers = $experts->select('lawyers.*')->paginate(100);
        $lawyers->appends(\request()->all());
        return new LawyersCollection($lawyers);
    }

    public function lawyer($id)
    {
        $expert = \Cache::remember('lawyer-' . $id, 1000, function () use ($id) {
            return Lawyer::with('expertises')->findOrFail($id);
        });
        return new LawyerResource($expert);
    }

    public function bookmark($id)
    {
        $user = \Auth::user();
        $rule = Lawyer::findOrFail($id);
        $type = true;
        if ($rule->is_bookmarked) {
            $rule->bookmarks()->detach($user->id);
            $type = false;
        } else {
            $rule->bookmarks()->attach($user->id);
        }
        return response()->json([
            'isBookmarked' => $type
        ]);
    }

    public function grades()
    {
        $fileds = \Cache::remember('grades', 10000, function () {
            return LawyerGrade::select('id', 'name')->get();
        });
        return $fileds;
    }

    public function expertises()
    {
        $fileds = \Cache::remember('expertises', 10000, function () {
            return Expertise::select('id', 'name')->get();
        });
        return $fileds;
    }

    public function storeExpertise(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|min:3'
        ]);
        $expertise = Expertise::firstOrCreate([
            'name' => trim(clear_text($request->input('name')))
        ]);
        \Cache::forget('expertises');
        return [
            'id' => $expertise->id,
            'name' => $expertise->name
        ];
    }
    public function getInfo()
    {
        $user = \Auth::user();
        $expert = Lawyer::whereUserId($user->id)->first();
        return [
            'name'=>$expert->user->name,
            'licenseNumber'=>$expert->license_number,
            'address'=>$expert->address,
            'province'=>[
                [
                    'id'=>$expert->province->id,
                    'name'=>$expert->province->name,
                ]
            ],
            'city'=>[
          [
              'id'=>$expert->city->id,
              'name'=>$expert->city->name,
          ]
            ],
            'grade'=>[
                [
                    'id'=>$expert->gradem->id,
                    'name'=>$expert->gradem->name,
                ]
            ],
            'expertises'=>$expert->expertises,
            'expireDate'=>$expert->expire_date,
        ];
    }

    public function updateInfo(Request $request)
    {
        $this->validate($request, [
            'gradeId' => 'required|numeric',
            'name' => 'required|string',
            'licenseNumber' => 'required|numeric',
            'expireDate' => 'required|date_format:Y/m/d',
            'provinceId' => 'required|numeric',
            'cityId' => 'required|numeric',
            'address' => 'required|string',
            'expertises'=>'nullable|string'
        ]);
        $grade = LawyerGrade::findOrFail($request->input('gradeId'));
        $province = Province::findOrFail($request->input('provinceId'));
        $city = Province::findOrFail($request->input('cityId'));
        $user = \Auth::user();
        $expert = Lawyer::whereUserId($user->id)->first();
        $user->name = $request->input('name');
        $user->save();
        $expert->update([
            'grade_id' =>$grade->id,
            'license_number' => $request->input('licenseNumber'),
            'expire_date' => toGregorian($request->input('expireDate')),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_area'=>$province->name,
            'city_area'=>$city->name,
            'address'=>$request->input('address'),
            'grade'=>$grade->name,
        ]);
        $expertisesId = explode(',',$request->input('expertises'));
        $expertises = Expertise::whereIn('id',$expertisesId)->get()->pluck('id')->toArray();
        $expert->expertises()->sync($expertises);
        \Cache::forget('lawyer-'.$expert->id);
        return response()->json([
            'message'=>'Updated Successfully'
        ]);

    }
}
