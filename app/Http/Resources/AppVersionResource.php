<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'changes'=>$this->changes,
            'name'=>$this->name,
            'link'=>$this->link,
            'forceUpdate'=>$this->force_update,
        ];
    }
}
