<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SeasonCollection extends ResourceCollection
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
               'text'=>$item->text,
               'number'=>$item->number,
               'clauses'=> new ClauseCollection($item->clauses),
               'topics'=> new TopicCollection($item->topics),

           ];
        });
    }
}
