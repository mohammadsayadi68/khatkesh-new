<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfflineRuleData extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data'=>[
                'id' => $this->id,
                'title' => $this->title,
                'isSpecial' => $this->is_special,
                'type' => $this->typeName,
                'implementDate' => $this->implement_date ? toJalali($this->implement_date) : null,
                'approvalDate' => $this->approval_date ? toJalali($this->approval_date) : null,
                'approvalAuthority' => $this->approval_authority,
                'countClauses' => $this->count_clauses
            ],
            'structures'=>new RuleStructureCollection($this->strurctures),
        ];
    }
}
