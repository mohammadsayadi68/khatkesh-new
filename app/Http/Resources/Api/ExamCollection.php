<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamCollection extends ResourceCollection
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
                'title' => $item->title,
                'description' => $item->description,
                'price' => $item->price,
                'isBought' => $item->is_bought,
                'numberOfQuestion' => count($item->questions),
                'time' => $item->time,
                'publisher' => $item->publisher
            ];
        });

    }


    public function with($request)
    {
        return [
            'status' => "success"
        ];
    }
}
