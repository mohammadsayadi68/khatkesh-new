<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClauseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item){
            return [
                'mainId'=>$item->main_id,
                'id'=>$item->id,
                'number'=>$item->number,
                'extinct'=>$item->extinct,
                'principle'=>$item->principle,
                'text'=>$item->new_text,
                'notes'=>new NoteCollection($item->notes),
                'paragraphs'=>new ParagraphCollection($item->paragraphs),
            ];
        });
    }
}
