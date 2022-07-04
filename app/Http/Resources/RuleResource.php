<?php

namespace App\Http\Resources;

use App\Season;
use Illuminate\Http\Resources\Json\JsonResource;

class RuleResource extends JsonResource
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
            'info'=>[
                'id'=>$this->id,
                'title'=>$this->title,
                'isSpecial'=>$this->is_special,
                'type'=>$this->typeName,
                'implementDate'=>$this->implement_date ? toJalali($this->implement_date) : null,
                'approvalDate'=>$this->approval_date ? toJalali($this->approval_date) : null,
                'approvalAuthority'=>$this->approval_authority,
            ],
            'sections'=> new SectionCollection($this->sections),
            'books'=> new BookCollection($this->books),
            'covers'=> new CoverCollection($this->covers),
            'chapters'=> new ChapterCollection($this->chapters),
            'seasons'=> new SeasonCollection($this->seasons),
            'clauses'=> new ClauseCollection($this->clauses),
            'notes'=> new NoteCollection($this->notes),
            'topics'=> new TopicCollection($this->topics),
            'episodes'=> new EpisodeCollection($this->episodes),
            'countClauses'=>10,
        ];
    }
}
