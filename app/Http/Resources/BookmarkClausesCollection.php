<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BookmarkClausesCollection extends ResourceCollection
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
                'rule'=>[
                    'title'=>$item->rule->title,
                    'id'=>$item->rule->id,
                ],
                'id' => $item->clause->id,
                'title' => $item->clause->title,
                'number' => $item->clause->number,
            ];
        });
    }
}
