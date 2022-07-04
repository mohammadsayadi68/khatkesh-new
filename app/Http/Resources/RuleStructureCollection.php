<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RuleStructureCollection extends ResourceCollection
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
            $text = $item->structureable->text;
            $text = strip_tags($text,'<br><img>');
            $data = [
                'id'=>$item->structureable->id,
                'number'=>$item->structureable->number,
                'name'=>$item->structureable->name,
                'text'=>$text,
            ];
            if (count($item->childs))
                $data['childs'] = new RuleStructureCollection($item->childs);
            if ($item->type=='clause'){
                $data['extinct'] = $item->structureable->extinct;
                $data['principle'] = $item->structureable->principle;
            }
            $response =  [
                'id'=>$item->id,
                'type'=>$item->type,
                'data'=>$data,
            ];

            return $response;
        });
    }
}
