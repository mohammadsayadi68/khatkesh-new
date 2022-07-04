<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SearchResultCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'title'=>$item->title,
                'categoryTitle'=>null,
                'implementDate'=>$item->implement_date ? toJalali($item->implement_date) : null,
                'approvalDate'=>$item->approval_date ? toJalali($item->approval_date) : null,
                'approvalAuthority'=>$item->approval_authority,
            ];
        });
    }
}
