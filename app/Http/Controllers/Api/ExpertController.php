<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Expert;
use App\ExpertField;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpertResource;
use App\Http\Resources\ExpertsCollection;
use App\Http\Resources\LawyersCollection;
use App\Province;
use Illuminate\Http\Request;

class ExpertController extends Controller
{
    public function experts()
    {
        $this->validate(request(), [
            'fullname' => 'nullable|string',
            'minExpireDate' => 'nullable|date_format:Y/m/d',
            'maxExpireDate' => 'nullable|date_format:Y/m/d',
            'provinceId' => 'nullable|numeric',
            'cityId' => 'nullable|numeric',
            'fieldId' => 'nullable|numeric'
        ]);
        $fullname = request()->get('fullname');
        $minExpireDate = request()->get('minExpireDate');
        $maxExpireDate = request()->get('maxExpireDate');
        $provinceId = request()->get('provinceId');
        $cityId = request()->get('cityId');
        $fieldId = request()->get('fieldId');


        $experts = Expert::with('user');
        if ($fullname) {
            $experts = $experts
                ->leftJoin('users', 'users.id', '=', 'experts.user_id')
                ->where('users.name', 'LIKE', "%$fullname%");
        }
        if ($minExpireDate) {
            $minExpireDate = toGregorian($minExpireDate);
            $experts = $experts->whereDate('experts.expire_date', '>=', $minExpireDate);
        }
        if ($maxExpireDate) {
            $maxExpireDate = toGregorian($maxExpireDate);
            $experts = $experts->whereDate('experts.expire_date', '<=', $maxExpireDate);
        }
        if ($provinceId) {
            $experts = $experts->where('experts.province_id', $provinceId);
        }
        if ($cityId) {
            $experts = $experts->where('experts.city_id', $cityId);
        }
        if ($fieldId) {
            $experts = $experts->where('experts.field_id', $fieldId);
        }
        $experts = $experts->select('experts.*')->paginate(100);
        $experts->appends(\request()->all());
        return new ExpertsCollection($experts);
    }

    public function expert($id)
    {
        $expert = \Cache::remember('expert-' . $id, 1000, function () use ($id) {
            return Expert::findOrFail($id);
        });
        return new ExpertResource($expert);
    }

    public function bookmark($id)
    {
        $user = \Auth::user();
        $rule = Expert::findOrFail($id);
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

    public function expertAndLawyerBookmarks()
    {
        $experts = \Auth::user()->experts;
        $lawyers = \Auth::user()->lawyers;
        return response()->json([
            'experts' => new ExpertsCollection($experts),
            'lawyers' => new LawyersCollection($lawyers)
        ]);
    }

    public function fields()
    {
        $fileds = \Cache::remember('fields', 10000, function () {
            return ExpertField::select('id', 'name')->get();
        });
        return $fileds;
    }

    public function getInfo()
    {
        $user = \Auth::user();
        $expert = Expert::whereUserId($user->id)->first();
        return [
            'name' => $expert->user->name,
            'licenseNumber' => $expert->license_number,
            'address' => $expert->address,
            'province' => [
                [
                    'id' => $expert->province->id,
                    'name' => $expert->province->name,
                ]
            ],
            'city' => [
                [
                    'id' => $expert->city->id,
                    'name' => $expert->city->name,
                ]
            ],
            'field' => [
                [
                    'id' => $expert->field->id,
                    'name' => $expert->field->name,
                ]
            ],
            'expireDate' => $expert->expire_date,

        ];
    }

    public function updateInfo(Request $request)
    {
        $this->validate($request, [
            'fieldId' => 'required|numeric',
            'name' => 'required|string',
            'licenseNumber' => 'required|numeric',
            'expireDate' => 'required|date_format:Y/m/d',
            'provinceId' => 'required|numeric',
            'cityId' => 'required|numeric',
            'address' => 'required|string'
        ]);
        $field = ExpertField::findOrFail($request->input('fieldId'));
        $province = Province::findOrFail($request->input('provinceId'));
        $city = City::findOrFail($request->input('cityId'));
        $user = \Auth::user();
        $user->name = $request->input('name');
        $user->save();
        $expert = Expert::whereUserId($user->id)->first();
        $expert->update([
            'field_id' => $field->id,
            'license_number' => $request->input('licenseNumber'),
            'expire_date' => toGregorian($request->input('expireDate')),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => $request->input('address'),
            'province_area'=>$province->name,
            'city_area  '=>$city->name,
            'undergraduate_field'=>$field->name
        ]);
        \Cache::forget('expert-' . $expert->id);
        return response()->json([
            'message' => 'Updated Successfully'
        ]);

    }
}
