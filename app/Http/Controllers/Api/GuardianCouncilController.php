<?php

namespace App\Http\Controllers\Api;

use App\GuardianCouncilAnswer;
use App\GuardianCouncilCategory;
use App\Http\Resources\GuardianCouncilStructureResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GuardianCouncilController extends Controller
{
    public function rule($ruleId)
    {
        $questions = GuardianCouncilAnswer::whereRuleId($ruleId)->get()->groupBy('structure_id');
        $response = [];
        foreach ($questions as $structureId=>$question){
            $response[$structureId] = [];
            $categories = $question->groupBy('guardian_council_category_id');
            foreach ($categories as $categoryId=>$category){
                $data = [
                    'name'=>GuardianCouncilCategory::findOrFail($categoryId)->name,
                    'questions'=>[]
                ];
                foreach ($category as $item ){
                    $data['questions'][]=$item;
                }
                $response[$structureId][] = $data;
                $data = [];
            }
        }
        $path = public_path('guardian-councils/');
        if (!is_dir($path))
            \File::makeDirectory($path);
        $filename = $ruleId.'.json';
        \File::put($path.$filename,json_encode($response,JSON_UNESCAPED_UNICODE));

        return $response;

    }
}
