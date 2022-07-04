<?php


namespace App\Common;


use App\Book;
use App\CategoryRule;
use App\Chapter;
use App\Clause;
use App\Cover;
use App\Episode;
use App\Interpretation;
use App\Note;
use App\Paragraph;
use App\Rule;
use App\RuleImageResource;
use App\RuleResource;
use App\RuleTree;
use App\Season;
use App\Section;
use App\Sturcture;
use App\Topic;

class RulesBot
{
    private $category;
    private $filter;
    private $type;
    private $status;
    private $countClauses = 0;
    private $currentRule;
    private $proxy;

    public function setCategory(CategoryRule $categoryRule)
    {
        $this->category = $categoryRule;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }


    public static function getInstance()
    {
        static $instance = null;
        if (!$instance)
            $instance = new RulesBot();
        return $instance;
    }

    public function storeWithCategory(CategoryRule $categoryRule)
    {
        $this->category = $categoryRule;
        $this->storeData($this->category->resource_id);
    }

    public function showTitleAndIds(CategoryRule $categoryRule)
    {
        $page = 1;
        $titles = [];
        $ids = [];
        do {
            $out = $this->getDataAndParse($categoryRule->resource_id, $page, 1000);
            $ids = array_merge($ids, $out[2]);
            $titles = array_merge($titles, $out[4]);
            $page++;
        } while (false);
        return [
            'titles' => $titles,
            'ids' => $ids
        ];
    }

    public function storeWithIds($ids)
    {
        foreach ($ids as $id) {
            $this->storeRule($id);

        }
    }

    public function setTypeAndStatus($type, $status)
    {
        $this->type = $type;
        $this->status = $status;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;

    }

    public function storeByFilter($pageNumber = 1)
    {
        $out = $this->getDataByFilterAndParse($pageNumber);
        $ids = $out[2];
        foreach ($ids as $key => $id) {
            try {
                $this->storeRule($id);
            } catch (\Exception $exception) {
                dump($exception->getMessage());
            }

        }
        if (count($ids)) {
            $pageNumber++;
            $this->storeByFilter($pageNumber);
        }
    }

    private function getDataByFilterAndParse($pageNumber = 1, $pageSize = 200)
    {
        $html = Client::getInstance()->getBody('http://qavanin.ir/?PageNumber=' . $pageNumber . '&PageSize=200', 'GET');
        $pattern = '~<a(.*?)href="/Law/TreeText/([^"]+)"(.*?)>(.*?)</a>~';
        preg_match_all($pattern, $html, $out);
        return $out;
    }

    private function getDataAndParse($category, $pageNumber = 1, $pageSize = 200)
    {
        $html = Client::getInstance()->getBody('http://qavanin.ir/Law/', 'POST', [
            'form_params' => [
                'drpApprover' => $category,
                'PageSize' => 200,
                'PageNumber' => $pageNumber,
            ]
        ]);
        $pattern = '~<a(.*?)href="/Law/TreeText/([^"]+)"(.*?)>(.*?)</a>~';
        preg_match_all($pattern, $html, $out);
        return $out;
    }

    private function storeData($category, $pageNumber = 1)
    {
        $out = $this->getDataAndParse($category, $pageNumber);
        $ids = $out[2];
        foreach ($ids as $key => $id) {
            $this->storeWithRuleId($id);
        }
        if (count($ids)) {
            $pageNumber++;
            $this->storeData($category, $pageNumber);
        }

    }

    public function storeWithRuleId($id)
    {
        $this->storeRule($id);
    }

    public function storeRule($id)
    {
        $rule = Rule::whereMainId($id)->first();
        if ($rule and $rule->stored)
            return;
        $this->countClauses = 0;
        $attributeUrl = 'http://qavanin.ir/Law/Attribute/' . $id;
        $html = Client::getInstance()->getBody($attributeUrl, 'GET', []);
        if (!$html)
            return;
        sleep(2);
        $html = str_replace('ي', 'ی', $html);
        $html = str_replace('ك', 'ک', $html);
        $pattern = '~<td>(.*?)</td>~';
        preg_match_all($pattern, $html, $attributes);
        $patternTitle = '/<h1 class=\'mytitle\'(.*?)>(.*)<\/h1>/is';
        preg_match($patternTitle, $html, $title);
        $title = $title[2] ?? '';
        if (!$title)
            return;
        foreach ($attributes[1] as $attributeData) {
            if (!strHas($attributeData, 'span'))
                continue;
            $attributeData = strip_tags($attributeData);
            $attributeData = array_map(function ($item) {
                return trim($item);
            }, explode(':', $attributeData));
            $typeName = '';
            switch ($attributeData[0]) {
                case "نوع قانون":
                    $type = \App\Constants\Rule::NORMAL_LAW;
                    $typeName = $attributeData[1];
                    switch ($attributeData[1]) {
                        case "اساسی":
                            $type = \App\Constants\Rule::CONSTITUTION;
                            break;
                    }
                    break;
                case "تاریخ تصویب":
                    $approvalDate = $attributeData[1];
                    if (!$approvalDate) {
                        $approvalDate = null;
                        break;
                    }

                    $approvalDate = toGregorian($approvalDate);
                    break;
                case "تاریخ اجرا":
                    $implementDate = $attributeData[1];
                    if (!$implementDate) {
                        $implementDate = null;
                        break;
                    }
                    $implementDate = toGregorian($implementDate);
                    break;
                case "مرجع تصویب":
                    $approvalAuthority = $attributeData[1];
                    break;
            }

        }
        $category = CategoryRule::whereTitle($approvalAuthority)->first();
        if (!$category) {
            $category = new CategoryRule();
            $category->title = $approvalAuthority;
            $category->save();
        }
        $this->category = $category;


        $rule = Rule::withTrashed()->firstOrNew([
            'main_id' => $id
        ]);
        $rule->type = $type;
        $rule->n_type = $this->type ?  $this->type : '1';
        $rule->status_approved = $this->status ? $this->status : '1';
        $rule->title = $title;
        $rule->approval_date = $approvalDate;
        $rule->implement_date = $implementDate;
        $rule->type_name = $typeName;
        $rule->category_rule_id = $this->category->id;
        $rule->approval_authority = $approvalAuthority;
        $rule->save();
        $this->currentRule = $rule;

//        try {
//            $this->storeTree($rule);
//        } catch (\Exception $exception) {
//            dump($exception->getMessage());
//        }
        $rule->count_clauses = $this->countClauses;
        $rule->stored = 1;
        $rule->save();
        $this->storeTree($rule);
        \Cache::forget('rule-' . $rule->id);
        \Cache::forget('rule-data-' . $rule->id);
        \Cache::forget('rule-structure-' . $rule->id);
    }

    private function storeTree(Rule $rule)
    {
        $client = Client::getInstance();
        $lawUrl = 'http://qavanin.ir/Law/TreeText/' . $rule->main_id;
        $rule->content = $this->getHtml($lawUrl, true);
        $rule->save();
        return;
        $treeUrl = 'http://qavanin.ir/Law/GetTree/' . $rule->main_id;
        $tree = json_decode($client->getBody($treeUrl), true);
        if (count($tree) <= 1)
            return;
        $lawHtml = $client->getBody($lawUrl);
        $lawHtml = str_replace('ي', 'ی', $lawHtml);
        $lawHtml = str_replace('ك', 'ک', $lawHtml);

        foreach ($tree as $key => $treeItem) {
            if (strHas(' ' . $treeItem['textField'] . ' ', 'متن')) {
                $this->storeRuleText($rule, $lawHtml, $tree, $key);
            }
            if (strHas(' ' . $treeItem['textField'] . ' ', 'پيوست')) {
                $this->storeAttachment($rule, $lawHtml, $tree, $key);
            }
            if (strHas(' ' . $treeItem['textField'] . ' ', 'امضا')) {
                $this->storeSignature($rule, $lawHtml, $tree, $key);
            }
            if (strHas(' ' . $treeItem['textField'] . ' ', 'موخره')) {
                $this->storeAfterAll($rule, $lawHtml, $tree, $key);
            }
            if (strHas(' ' . $treeItem['textField'] . ' ', 'مقدمه')) {
                $this->storeIntroduction($rule, $lawHtml, $tree, $key);
            }
            $this->checkChildren($treeItem, $rule, $lawHtml, $tree, $key);
        }
    }

    private function getNextPrimaryKey($tree, $currentIndex, $note = false)
    {
        if (!$note and count($tree[$currentIndex]['children']))
            return $tree[$currentIndex]['children'][0]['primaryKey'];
        if (isset($tree[$currentIndex + 1]))
            return $tree[$currentIndex + 1]['primaryKey'];
        return false;
    }

    private function storeClause($clauseable, $lawHtml, $tree, $key, $principle = false)
    {
        $textTitle = $tree[$key]['textField'];
        $search = 'ماده ';
        if ($principle)
            $search = 'اصل ';
        $number = str_replace($search, '', $textTitle);
        $primaryKey = $tree[$key]['primaryKey'];
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $clauseHtml = $this->getContentBetweenKeys($lawHtml, $primaryKey, $nextPrimaryKey, []);
        $extinct = strHas($clauseHtml, 'color:Red');
        $clauseHtml = $this->replaceTags($clauseHtml, ['p', 'a', 'img', 'span']);
        $clause = Clause::withTrashed()->firstOrNew([
            'main_id' => $primaryKey
        ]);

        if (mb_substr($clauseHtml, 0, 1) === '-')
            $clauseHtml = mb_substr($clauseHtml, 1, mb_strlen($clauseHtml));
        $clauseHtml = str_replace($this->convertTextToOrginal($textTitle), '', $clauseHtml);
        $clause->clauseable()->associate($clauseable);
        $clause->text = $clauseHtml;
        if (is_numeric($number) || trim($number) == 'واحده' || strHas($number, 'مكرر') || !convertAlphabetToNumber($number))
            $clause->number = $number;
        else
            $clause->number = convertAlphabetToNumber($number);

        $clause->extinct = $extinct;
        $clause->principle = $principle;
        $clause->save();
        $this->storeStructure($clause, $primaryKey, 'clause');
        $this->storeRuleTree($clause, $primaryKey, $textTitle, $clauseable);

        $children = $tree[$key]['children'];
        $this->countClauses++;
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $clause, $lawHtml, $children, $childKey);
        }
    }

    public function convertTextToOrginal($title)
    {
        $title = str_replace('ي', 'ی', $title);
        $title = str_replace('ك', 'ک', $title);
        return $title;
    }

    private function storeTopic($topicable, $lawHtml, $tree, $key)
    {
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('مبحث', '', $textTitle);
        $number = str_replace(' ', '', $number);
        $primaryKey = $tree[$key]['primaryKey'];
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);

        $clauseHtml = $this->getContentBetweenKeys($lawHtml, $primaryKey, $nextPrimaryKey, ['p']);
        $clauseText = mb_substr($clauseHtml, 1, mb_strlen($clauseHtml));
        $clause = Topic::withTrashed()->firstOrNew([
            'main_id' => $primaryKey
        ]);
        $clauseText = explode('</span>', $clauseText);
        if (count($clauseText) == 1)
            $clauseText = $clauseText[0];
        else
            $clauseText = $clauseText[1];
        $clause->topicable()->associate($topicable);
        $clause->text = $clauseText;
        $clause->number = $number;
        $clause->save();
        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $clause, $lawHtml, $children, $childKey);
        }
    }

    private function storeRuleText(Rule $rule, $html, $tree, $key)
    {
        $primaryKey = $tree[$key]['primaryKey'];
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $textHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p']);
        $rule->text = $textHtml;
        $rule->save();
        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $rule, $html, $children, $childKey);
            if (strHas(' ' . $child['textField'] . ' ', 'مقدمه'))
                $this->storeIntroduction($rule, $html, $children, $childKey);
        }

    }

    private function storeIntroduction($rule, $html, $tree, $key)
    {
        $primaryKey = $tree[$key]['primaryKey'];
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $textHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p']);
        $rule->introduction = $textHtml;
        $rule->save();
        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $rule, $html, $children, $childKey);
        }
    }




    private function storeNote($noteable, $html, $tree, $key, $parentData = null)
    {
        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('تبصره ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);

        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['']);
        $extinct = strHas($noteHtml, 'color:Red');
        $noteHtml = $this->replaceTags($noteHtml, ['p', 'a', 'img']);
        $noteHtml = explode('</span>', $noteHtml);
        if (count($noteHtml) == 1)
            $noteHtml = $noteHtml[0];
        else
            $noteHtml = $noteHtml[1];

        if (mb_substr($noteHtml, 0, 1) === '-')
            $noteHtml = mb_substr($noteHtml, 1, mb_strlen($noteHtml));
        $clause = Note::withTrashed()->firstOrNew([
            'main_id' => $primaryKey
        ]);
        if ($number == 'تبصره')
            $number = null;

        $clause->noteable()->associate($noteable);
        $clause->text = $noteHtml;
        $clause->extinct = $extinct;
        $clause->number = $number == '' ? null : $number;
        $clause->save();
        $this->storeStructure($clause, $primaryKey, 'note', $noteable);
        $this->storeRuleTree($clause, $primaryKey, $textTitle, $noteable);

        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $clause, $html, $children, $childKey);
        }

    }

    private function storeParagraph($paragraphable, $html, $tree, $key, $parentData = [])
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('بند ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key, true);
        if (!$nextPrimaryKey and count($tree[$key]['children'])) {
            $nextPrimaryKey = $primaryKey + count($tree[$key]['children']) + 1;
        }
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['span']);

        $noteText = str_replace(['-', '<p>'], '', $noteHtml);
        $noteText = str_replace('</p>', '<br>', $noteText);
        $clause = Paragraph::withTrashed()->firstOrNew([
            'main_id' => $primaryKey
        ]);
        $clause->paragraphable()->associate($paragraphable);
        $clause->text = $noteText;
        $clause->number = $number;
        $clause->save();
        $this->storeStructure($clause, $primaryKey, 'paragraph', $paragraphable);
        $this->storeRuleTree($clause, $primaryKey, $textTitle, $paragraphable);
        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $clause, $html, $children, $childKey);
        }

    }

    private function storeSeason($seasonable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('فصل ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p']);

        $season = Season::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $noteHtml = explode('</span>', $noteHtml);
        if (count($noteHtml) == 1)
            $noteHtml = $noteHtml[0];
        else
            $noteHtml = $noteHtml[1];
        $noteHtml = explode('<br>', $noteHtml);
        $name = $noteHtml[0];
        $name = str_replace(['-', '_', '–'], '', $name);
        unset($noteHtml[0]);
        $noteHtml = implode('<br>', $noteHtml);
        if (strlen($name) > 100) {
            $noteHtml = $name . '<br>' . $noteHtml;
            $name = null;
        }
        $season->number = $number;
        $season->name = $name;
        $season->text = $noteHtml;
        $season->seasonable()->associate($seasonable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'season');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $seasonable);

        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeCover($coverable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('جلد ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p']);

        $season = Cover::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $season->number = $number;
        $season->name = $noteHtml;
        $season->coverable()->associate($coverable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'cover');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $coverable);
        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeEpisode($coverable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('قسمت ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p', 'span']);

        $season = Episode::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $noteHtml = explode('</span>', $noteHtml);
        if (count($noteHtml) == 1)
            $noteHtml = $noteHtml[0];
        else
            $noteHtml = $noteHtml[1];
        $noteHtml = explode('<br>', $noteHtml);
        $name = $noteHtml[0];
        $name = str_replace(['-', '_', '–'], '', $name);
        unset($noteHtml[0]);
        $noteHtml = implode('<br>', $noteHtml);
        if (strlen($name) > 100) {
            $noteHtml = $name . '<br>' . $noteHtml;
            $name = null;
        }
        $season->number = $number;
        $season->name = $name;
        $season->text = $noteHtml;
        $season->episodeable()->associate($coverable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'episode');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $coverable);


        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeInterpretation($interpretationable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = $textTitle;
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p', 'span', 'a', 'img']);

        $season = Interpretation::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $season->name = $number;
        $season->text = $noteHtml;
        $season->interpretationable()->associate($interpretationable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'interpretation');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $interpretationable);

        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeSection($sectionable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('بخش ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p', 'span', 'a', 'img']);
        $season = Section::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $season->name = $noteHtml;
        $season->number = $number;
        $season->sectionable()->associate($sectionable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'section');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $sectionable);


        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeBook($bookable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('کتاب ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p', 'a', 'img']);
        $season = Book::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $season->number = $number;
        $season->name = $noteHtml;
        $season->bookable()->associate($bookable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'book');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $bookable);

        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function storeChapter($chapterable, $html, $tree, $key)
    {

        $primaryKey = $tree[$key]['primaryKey'];
        $textTitle = $tree[$key]['textField'];
        $number = str_replace('باب ', '', $textTitle);
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $noteHtml = $this->getContentBetweenKeys($html, $primaryKey, $nextPrimaryKey, ['p', 'a', 'img']);
        $season = Chapter::firstOrNew([
            'main_id' => $primaryKey
        ]);
        $season->number = $number;
        $season->name = $noteHtml;
        $season->chapterable()->associate($chapterable);
        $season->save();
        $this->storeStructure($season, $primaryKey, 'chapter');
        $this->storeRuleTree($season, $primaryKey, $textTitle, $chapterable);


        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($child, $season, $html, $children, $childKey);
        }


    }

    private function checkChildren($treeItem, $morphAble, $lawHtml, $tree, $key)
    {
        $primaryKey = $tree[$key]['primaryKey'];


        if (strHas(' ' . $treeItem['textField'] . ' ', 'ماده')) {
            $this->storeClause($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'اصل')) {
            $this->storeClause($morphAble, $lawHtml, $tree, $key, true);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'تفسير') and !strHas(' ' . $treeItem['textField'] . ' ', 'متن تفسير')) {
            $this->storeInterpretation($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'تبصره')) {
            $this->storeNote($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'بند')) {
            $this->storeParagraph($morphAble, $lawHtml, $tree, $key, [
                'parent' => $tree,
                'key' => $key
            ]);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'مبحث')) {
            $this->storeTopic($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'فصل')) {
            $this->storeSeason($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'بخش')) {
            $this->storeSection($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'جلد')) {
            $this->storeCover($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'كتاب')) {
            $this->storeBook($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'باب')) {
            $this->storeChapter($morphAble, $lawHtml, $tree, $key);
        }
        if (strHas(' ' . $treeItem['textField'] . ' ', 'قسمت')) {
            $this->storeEpisode($morphAble, $lawHtml, $tree, $key);
        }
    }


    private function getContentBetweenKeys($html, $firstPrimaryKey, $secondPrimaryKey, $replaceTags = [])
    {
        $html = explode('<a id="' . $firstPrimaryKey . '"></a>', $html);
        if (count($html) <= 1)
            dump($this->currentRule);
        else
            $html = $html[1];
        if ($secondPrimaryKey)
            $html = explode('<a id="' . $secondPrimaryKey . '"></a>', $html)[0];
        else {
            $html = explode('</p>', $html)[0];
        }
        $html = $this->replaceTags($html, $replaceTags);
        return $html;
    }

    public function replaceTags($html, $replaceTags)
    {
        foreach ($replaceTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>/is', "", $html);
            $html = str_replace("</$tag>", '', $html);
        }
        return $html;
    }

    public function storeStructure($structureable, $id, $type, $parent = null)
    {
        if ($parent) {
            $structureableType = get_class($parent);
            $structureableId = $parent->id;
            if ($structureableType == 'App\\Clause' || $structureableType == 'App\\Note') {
                $parent = Sturcture::where('structureable_type', $structureableType)->where('structureable_id', $structureableId)->first();
            } else {
                $parent = null;
            }
        }
        $structure = Sturcture::firstOrNew([
            'main_id' => $id,
            'rule_id' => $this->currentRule->id
        ]);
        $structure->structureable()->associate($structureable);
        $structure->type = $type;
        $structure->parent_id = optional($parent)->id;
        $structure->save();
    }

    public function storeRuleTree($ruletreeable, $id, $name, $parent = null)
    {
        if ($parent) {
            $structureableType = get_class($parent);
            $structureableId = $parent->id;
            if ($structureableType != 'App\\Rule') {
                $parent = RuleTree::where('ruletreeable_type', $structureableType)->where('ruletreeable_id', $structureableId)->first();
            } else {
                $parent = null;
            }
        }
        $structure = RuleTree::firstOrNew([
            'main_id' => $id,
            'rule_id' => $this->currentRule->id
        ]);
        $structure->ruletreeable()->associate($ruletreeable);
        $structure->parent_id = optional($parent)->id;
        $structure->name = $name;
        $structure->save();
    }

    public function storeContent($pageNumber = 1)
    {
        $this->filter = [];
        $out = $this->getDataByFilterAndParse($pageNumber);
        $ids = $out[2];
        $response = true;
        foreach ($ids as $key => $id) {
            $response = $this->storeContentRule($id);
            if (!$response) {
                echo "Proxy expired";
                break;
            }
        }
        if ($response) {
            if (count($ids)) {
                $pageNumber++;
                $this->storeContent($pageNumber);
            }
        }

    }

    public function storeContentRule($id)
    {
        $rule = RuleResource::whereMainId($id)->first();
        if ($rule and $rule->stored)
            return true;
        $attributeUrl = 'http://qavanin.ir/Law/Attribute/' . $id;
        $lawUrl = 'http://qavanin.ir/Law/TreeText/' . $id;
        $treeUrl = 'http://qavanin.ir/Law/GetTree/' . $id;
        $imageUrl = 'http://qavanin.ir/Law/ImageAllView/' . $id . '?type=1';
        $subjectUrl = 'http://qavanin.ir/Law/SubjectIndex/' . $id;
        $statusUrl = 'http://qavanin.ir/Law/StatusIndex/' . $id;
        $relatedUrl = 'http://qavanin.ir/Law/RelatedIndex/' . $id;
        $tree = $this->getHtml($treeUrl, true);
        if ($tree == false || $tree == 0 || $tree === '0') {
            return false;
        }
        $rule = new RuleResource();
        $rule->text = $this->getHtml($lawUrl);
        $rule->tree = $tree;
        $rule->info = $this->getHtml($attributeUrl);
        $rule->image = $this->getHtml($imageUrl);
        $rule->subject = $this->getHtml($subjectUrl);
        $rule->status = $this->getHtml($statusUrl);
        $rule->related = $this->getHtml($relatedUrl);
        $rule->main_id = $id;
        $rule->save();
        return true;
    }

    public function storeContentRuleTree($id)
    {
        $treeUrl = 'http://qavanin.ir/Law/GetTree/' . $id;
        $tree = Client::getInstance()->getBody($treeUrl, 'GET', []);
        if (!$tree) {
            return false;
        }
        $rule = new RuleResource();
        $rule->tree = $tree;
        $rule->main_id = $id;
        $rule->save();
        sleep(3);
        return true;
    }
    public function storeRuleImages($mainID,$ruleID)
    {
        sleep(4);
        $treeUrl = 'http://qavanin.ir/Law/ImageAllView/'.$mainID.'?type=1' ;
        $body = Client::getInstance()->getBody($treeUrl, 'GET', []);
        if (!$body) {
            return false;
        }
        $regex = '/<img src="\/resource\/regulation\/'.$mainID.'\/eblagh\/(.*?)"/si';
        preg_match_all($regex,$body,$out);
        foreach ($out[1] as $name){
            try{
                $urlToDownload = 'http://qavanin.ir/resource/regulation/'.$mainID.'/eblagh/'.$name;
                download_file($urlToDownload,public_path('/rules/images/'.$ruleID.'/'.$name));
                RuleImageResource::updateOrCreate([
                    'main_id'=>$mainID,
                    'name'=>$name,
                    'rule_id'=>$ruleID
                ],[]);
            }catch (\Exception $exception){
            dump($exception->getMessage());
            }

        }
        return true;
    }


    public function getHtml($url, $preventMinify = false)
    {
        $html = Client::getInstance()->getBody($url, 'GET', [

        ]);
        preg_match('/<div id="treeText"(.*?)>(.*?)<\/div>/si', $html, $out);
        $html = $out[2];
        if ($preventMinify)
            return $html;
        $html = preg_replace('/<section class="normal-section" id="sec-header">(.*?)<\/section>/si', '', $html);
        $html = preg_replace('/<footer class="main-footer" id="Links">(.*?)<\/footer>/si', '', $html);
        $html = preg_replace('/<div id="myModal" class="modal fade" role="dialog">(.*?)<\/div>/si', '', $html);
        $html = preg_replace('/<form action="\/Login\/Login" method="post">(.*?)<\/form>/si', '', $html);
        $html = preg_replace('/<head>(.*?)<\/head>/si', '', $html);
        $html = preg_replace('/<style>(.*?)<\/style>/si', '', $html);
        $html = $this->replaceTags($html, ['script', 'link']);
        return $this->sanitize_output($html);

    }

    public function sanitize_output($buffer)
    {

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }
}