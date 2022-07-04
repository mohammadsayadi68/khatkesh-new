<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamResultCollection extends ResourceCollection
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
                'examResultId' => $item->id,
                'id' => $item->exam->id,
                'title' => $item->exam->title,
                'price' => $item->exam->price,
                'time' => $item->exam->time,
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
