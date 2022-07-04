<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteCollection extends ResourceCollection
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
                'id'=>$item->id,
                'mainId'=>$item->main_id,
                'number'=>$item->number,
                'text'=>$item->new_text,
                'paragraphs'=>new ParagraphCollection($item->paragraphs)
            ];
        });
    }
}
