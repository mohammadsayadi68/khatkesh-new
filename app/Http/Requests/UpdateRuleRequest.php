<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'=>'required|string',
            'approval-date'=>'nullable|date_format:Y/m/d',
            'implement-date'=>'nullable|date_format:Y/m/d',
            'category'=>'required|numeric|exists:category_rules,id',
            'approval-authority'=>'nullable|string',
            'type'=>'required|integer|min:1|max:3',
            'status'=>'required|integer|in:0,1',
            'signature'=>'nullable|string',
            'text'=>'nullable|string',
            'price'=>'required|numeric'
        ];
    }
}
