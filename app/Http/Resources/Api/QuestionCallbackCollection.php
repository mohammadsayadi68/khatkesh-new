<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuestionCallbackCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            return [
                'point' => $item->point,
                'media' => [
                    'video' => [
                        'thumbnail' => $item->video_thumb,
                        'source' => $item->video
                    ],
                    'image' => [
                        'source' => $item->image
                    ]
                ]
            ];
        });

    }

    public function with($request)
    {
        return [
            'status' => 'success'
        ];
    }
}
