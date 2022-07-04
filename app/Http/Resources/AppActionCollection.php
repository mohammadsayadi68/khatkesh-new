<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AppActionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        $response = $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'icon' => $item->icon,
                'type' => $item->type,
                'action' => $item->action,
                'premium' => $item->premium,
                'disabled' => $item->disabled,
                'iconType' => $item->icon_type,
                'description' => $item->description,
                'disabledText' => $item->disabled_text,
            ];
        });
        $response[] = [
            'id' => 10,
            'name' => 'سوالات آزمون وکالت',
            'icon' => 'http://gilan-karshenasrasmi.ir/wp-content/uploads/2019/07/Asset_1.png',
            'type' => 'WEBVIEW',
            'action' => 'http://irrule.ir/webview/questions-1398?api_token='.\Auth::user()->api_token,
            'premium' => false,
            'disabled' => false,
            'iconType' => 'PHOTO',
        ];
        return $response;
    }
}
