<?php

namespace App\Console\Commands;

use App\Common\Client;
use App\Rule;
use App\RuleItemContent;
use App\RuleResource;
use Illuminate\Console\Command;

class ParseRuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rule:parse {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $rule;
    private $ruleResource;
    private $countClauses;

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function getHtml($url)
    {
        $html = Client::getInstance()->getBody($url, 'GET', [

        ]);
        if (!$html)
            dump("Empty");
        preg_match('/<div id="treeText"(.*?)>(.*?)<\/div>/si', $html, $out);
        $html = $out[2];
        return $html;
    }

    public function handle()
    {
        $this->countClauses = 0;
        $this->rule = Rule::findOrFail($this->argument('id'));
        if (!$this->rule->content) {
            $lawUrl = 'http://qavanin.ir/Law/TreeText/' . $this->rule->main_id;
            $this->rule->content = $this->getHtml($lawUrl);
            $this->rule->save();
            sleep(2);
        }
        if (!$this->rule->content)
            return;
        $this->ruleResource = RuleResource::whereMainId($this->rule->main_id)->firstOrFail();
        $this->storeTree();
        $this->rule->count_clauses = RuleItemContent::whereType(6)
            ->where('rule_id', $this->rule->id)
            ->where('extinct', 0)
            ->groupBy('number')
            ->count();
        $this->rule->save();


    }

    private function storeTree()
    {
        $tree = json_decode($this->ruleResource->tree, true);
        if (!is_array($tree))
            return;
        foreach ($tree as $key => $treeItem) {
            $this->checkChildren(null, $tree, $key);
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

    public function replaceTags($html, $replaceTags)
    {
        foreach ($replaceTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>/is', "", $html);
            $html = str_replace("</$tag>", '', $html);
        }
        return $html;
    }

    private function getContentBetweenKeys($html, $firstPrimaryKey, $secondPrimaryKey, $replaceTags = [])
    {
        $html = explode('<a id="' . $firstPrimaryKey . '"></a>', $html);
        if (count($html) <= 1)
            return '';
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

    private function checkChildren($morphAble, $tree, $key)
    {
        $textTitle = $this->convertTextToOrginal($tree[$key]['textField']);
        $textTitlePieces = explode(' ', $textTitle);
        $name = $textTitlePieces[0];
        unset($textTitlePieces[0]);
        $numberName = implode(' ', $textTitlePieces);
        $primaryKey = $tree[$key]['primaryKey'];
        $nextPrimaryKey = $this->getNextPrimaryKey($tree, $key);
        $clauseHtml = $this->getContentBetweenKeys($this->rule->content, $primaryKey, $nextPrimaryKey, []);
        $extinct = strHas($clauseHtml, 'color:Red');
        $clauseHtml = $this->replaceTags($clauseHtml, ['p', 'a', 'img', 'span', 'marquee']);
        if (mb_substr($clauseHtml, 0, 1) === '-')
            $clauseHtml = mb_substr($clauseHtml, 1, mb_strlen($clauseHtml));

        $content = str_replace($this->convertTextToOrginal($textTitle), '', $clauseHtml);
        $item = RuleItemContent::firstOrNew([
            'main_id' => $primaryKey,
            'rule_id' => $this->rule->id,
        ]);
        $item->extinct = $extinct;
        $item->parent_id = optional($morphAble)->id;
        $content = trim($content);
        $content = ltrim($content);
        $numberName = trim($numberName);
        $content = $this->convertTextToOrginal($content);
        if ($numberName) {
            $item->number_name = $numberName;
            if (trim($numberName) == 'واحده') {
                $number = 0;
            } else {
                $number = convertAlphabetToNumber($numberName);
                $number = convert2english($number);
            }
            if ($number == '')
                $number = null;
            if (!is_numeric($number)) {
                try {
                    $number = (integer)$numberName;
                    if ($number == 0) {
                        $number = null;
                    }
                } catch (\Exception $e) {

                }
            }
            $item->number = $number;
            if (strHas(mb_substr($content, 0, 10), 'ـ')) {
                $first = mb_substr($content, 0, 10);
                $content10 = mb_substr($content, 10, mb_strlen($content));
                $first = str_replace(sprintf('%s ـ', $numberName), '', $first);
                $first = str_replace(sprintf('%sـ', $numberName), '', $first);
                $first = str_replace(sprintf('%s  ـ', $numberName), '', $first);
                $first = str_replace(sprintf('%s ـ', convert2persian($numberName)), '', $first);
                $first = str_replace(sprintf('%sـ', convert2persian($numberName)), '', $first);
                $first = str_replace(sprintf('%s  ـ', convert2persian($numberName)), '', $first);
//                dump('X:' . mb_substr($content, 0, 4) . ' Y:' . sprintf('%s ـ ', $numberName). '||||' .$first);
                $content = $first . $content10;

            }
            $search = $name . ' ' . $numberName;
//            if (strHas($content,$search)){
            $content = str_replace($search, '', $content);
            if (mb_substr($content, 0, 4) == '<br>') {
                $content = mb_substr($content, 4, mb_strlen($content));
            }
            if (mb_substr($content, 0, 5) == ' <br>') {
                $content = mb_substr($content, 5, mb_strlen($content));
            }
            if (mb_substr($content, 0, 6) == '  <br>') {
                $content = mb_substr($content, 6, mb_strlen($content));
            }
//            }
//            if (mb_substr($content, 0, 4) === sprintf('%s ـ ',$number))
//                $content = mb_substr($content, 1, mb_strlen($content));
        }

        $item->content = $content;
        $item->extinct = $extinct;
        $item->name = $name;
        $item->type = RuleItemContent::$types[$name] ?? null;
        $item->save();
        if ($item->type == 6) {
            $this->countClauses++;
        }

        $children = $tree[$key]['children'];
        foreach ($children as $childKey => $child) {
            $this->checkChildren($item, $children, $childKey);
        }
    }

    public function convertTextToOrginal($title)
    {
        $title = str_replace('ي', 'ی', $title);
        $title = str_replace('ك', 'ک', $title);
        return $title;
    }
}
