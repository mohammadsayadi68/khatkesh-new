<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryRulesWithStructuresCollection extends ResourceCollection
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
                'rule' => [
                    'info' => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'isBookmarked' => $item->is_bookmarked,
                        'countStructures' => $item->sturctures_count,
                    ]
                ],
                'ruleStructures'=>new RuleStructureCollection($item->strurctures)
            ];
        });
    }
}
