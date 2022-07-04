<?php

namespace App\Http\Controllers\Api;

use App\Discount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function     check(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string',
        ]);
        $code = $request->input('code');
        $discount = Discount::whereCode($code)->first();
        if (!$discount)
            return response()->json([
                'message' => 'کد تخفیف وارد شده معتبر نمی باشد.',
                'status' => false
            ]);
        if ($discount->count == $discount->count_used)
            return response()->json([
                'message' => 'کد تخفیف وارد شده منقضی شده است.',
                'status' => false
            ]);
        if ($discount->has_used)
            return response()->json([
                'message' => 'کد تخفیف وارد شده قبلا استفاده شده است.',
                'status' => false
            ]);
        return response()->json([
            'percent' => $discount->percent,
            'status' => true
        ]);
    }
}
