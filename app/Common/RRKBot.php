<?php


namespace App\Common;


class RRKBot
{
    private $page = 1;

    public static function getInstance()
    {
        static $instance = null;
        if (!$instance)
            $instance = new RRKBot();
        return $instance;
    }

    public function crawl($categoryId)
    {
        $this->page = 1;
        $this->fetch($categoryId);
    }

    private function fetch($categoryId)
    {
        $page = $this->page;
        $url = "http://www.rrk.ir/Laws/?CatCode=$categoryId&PageNo=$page";
        $client = new Client();
        $response = $client->getBody($url);
        $this->proccess($response);

    }

    private function proccess($html)
    {
        $html = explode(' <table class="table table-striped">', $html);
        $html = $html[1];
        $html = explode('<div class="PagerContainer">', $html)[0];
        $pattern = '/<div class="cHand" onclick=\'OpenPage\("(.*?)",/';
        preg_match_all($pattern, $html, $matches);
        foreach ($matches[1] as $id) {
            $this->storeId($id);
        }
    }

    private function storeId($id)
    {
        $url = "http://www.rrk.ir/Laws/ShowLaw.aspx?Code=$id";
        $client = new Client();
        $response = $client->getBody($url);
        $pt = "/document.write\(unescape\('(.*?)'\)\)/";
        preg_match_all($pt, $response, $out);
        $body = $out[1][0];
        dd(utf8_urldecode($body));
    }

}