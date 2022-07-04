<?php


namespace App\Common;


use App\TableImage;

class TableConverter
{
    private $model;
    static $keys = [
        'bcc9f543-e737-4460-a064-861ee6d56f8f' => '5a87cfce-8992-488f-b824-184e2d6f4de7',
        '0223fc3e-951a-4c3e-afb2-fbb722c3980d' => '08e0517a-10e6-4bdb-b17c-81035576d514',
        'adedbe3e-3b85-4d94-8580-a028c656bba5' => 'd2cc09d6-4345-493b-9ab0-0655c235d54e',
        '1bb6aa7a-8663-4ef6-a7ca-01796ddd5076' => 'b355e360-96cd-4b09-b8d0-14fde027d3ac',
        '67da46dd-6781-4394-9882-c61b248a50aa' => 'dfba4323-5144-46c8-91c5-1ab5febbf422',
        'd345225b-236c-4ec8-80a6-c4ed1ebf896a' => '56e2583c-cbb5-4f6f-81d0-2949aae8f68a'
    ];

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function store()
    {
        return;
        $tables = $this->getTablesTag();
        foreach ($tables as $table) {
            $tableImage = TableImage::whereTable($table)->first();
            if ($tableImage) {
                $this->getShortCode($tableImage, $table);
            } else {
                $url = $this->generateImage($table);
                $this->storeModel($url, $table);
            }
        }
    }

    private function getTablesTag()
    {
        $text = $this->model->text;
        $pattern = '/(<TABLE[^>]*>(?:.|\n)*?<\/TABLE>)/';
        preg_match_all($pattern, $text, $result);

        return $result[1];
    }

    private function generateImage($tableTag)
    {
        $client = new Client();
        $userId = array_rand(self::$keys);
        $apiKey = self::$keys[$userId];
        $result = $client->getBody('https://hcti.io/v1/image', 'POST', [
            'form_params' => [
                'html' => $tableTag,
                'css' => ' table {
    direction: rtl;
    font-family: Amiri,sans-serif !important;
    border-collapse: collapse !important;

}
td{
    border:1px solid #424242;
    font-family: Amiri,sans-serif !important;
    font-size:20px

}',
                'google_fonts' => 'Amiri',
            ],
            'auth' => [$userId, $apiKey],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
        $result = json_decode($result, true);
        return $result['url'];
    }
    private function storeImage(TableImage $tableImage)
    {
        $base = '/images/tables/' . $tableImage->id . '.jpg';
        $path = public_path($base);
        download_file($tableImage->resource_url, $path);
        return $base;
    }

    private function storeModel($url, $table)
    {
        $tableImage = new TableImage();
        $tableImage->resource_url = $url;
        $tableImage->tableimageable()->associate($this->model);
        $tableImage->table = $table;
        $tableImage->save();
        $tableImage->url = $this->storeImage($tableImage);
        $tableImage->save();

        $this->getShortCode($tableImage, $table);

    }

    public function getShortCode(TableImage $tableImage, $table)
    {
        $shortcode = '[table_image id="' . $tableImage->id . '"]';
        $this->model->text = str_replace($table, $shortcode, $this->model->text);
        $this->model->has_rendered_table = true;
        $this->model->save();
    }
}