<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuestionCollection extends ResourceCollection
{

    protected $hasAttended;
    protected $examAttendedId;

    public function hasAttended($hasAttended, $examAttendedId){
        $this->hasAttended = $hasAttended;
        $this->examAttendedId = $examAttendedId;
        return $this;
    }



    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {

            $response = [
                'id' => $item->id,
                'examId' => $item->exam_id,
                'answer' => $item->answer,
                'description' => $item->title,
                'hasPoint' => $item->has_point,
                'choices' => [$item->first_choice, $item->second_choice, $item->third_choice, $item->fourth_choice],
            ];
            if ($item->has_point){
                $response ['pointType'] = $item->point_type;
                $response ['pointContent'] = $item->point_content;
            }

            if($this->hasAttended){
                $response['userAnswer'] =  $item->getUserAnswer($this->examAttendedId);
            }
            return $response;

        });

    }

    public function with($request)
    {
        return [
            'status' => "success"
        ];
    }
}
