<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SectionCollection extends ResourceCollection
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
                'name'=>$item->name,
                'number'=>$item->number,
                'seasons'=> new SeasonCollection($item->seasons),
                'chapters'=> new ChapterCollection($item->chapters),
                'clauses'=> new ClauseCollection($item->clauses),
                'episodes'=> new EpisodeCollection($item->episodes),
            ];
        });
    }
}
