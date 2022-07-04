<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->exam->id,
            'title' => $this->exam->title,
            'description' => $this->exam->description,
            'price' => $this->exam->price,
            'time' => $this->exam->time,
            'result'=>$this->result,
            'countQuestions'=>$this->exam->questions_count,

        ];
    }

    public function with($request)
    {
        return [
            'status' => 'success'
        ];
    }

}
