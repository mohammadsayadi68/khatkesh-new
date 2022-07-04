<?php

namespace App\Http\Controllers;

use App\ContentKeywordReplacement;
use App\Organization;
use App\Rule;
use App\RuleItemContent;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function single($slug)
    {
        $type = \request()->get('type','');
        $orginzation = Organization::whereName(str_replace('-',' ',$slug))->firstOrFail();
        $items = ContentKeywordReplacement::query()
            ->where('originable_type',Organization::class)
            ->where('originable_id',$orginzation->id);
        if ($type=='title'){
            $items = $items->where('keywordable_type',Rule::class);
        }else if($type=='content'){
            $items = $items->where('keywordable_type',RuleItemContent::class);
        }
        $items = $items->latest()->paginate();
        $items->appends([
            'type'=>$type
        ]);
        $title = $orginzation->name;
        return view('organization.single',compact('orginzation','title','items','type'));
    }
}
