<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EpisodeCollection extends ResourceCollection
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
                'text'=>$item->text,
                'name'=>$item->name,
                'number'=>$item->number,
                'books'=> new BookCollection($item->books),
                'seasons'=> new SeasonCollection($item->seasons),
                'clauses'=> new ClauseCollection($item->clauses),
                'chapters'=> new ChapterCollection($item->chapters),
            ];
        });
    }
}
