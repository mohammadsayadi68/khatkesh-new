<?php

namespace App\Console\Commands;

use App\Rule;
use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', -1);
        $sitemapPath = public_path('/sitemap.xml');
        $sitemap = SitemapIndex::create();
        $i = 1;
        $rules = \App\Rule::query()->has('items')
            ->with(['items'=>function($query){
                return $query->where(function ($q){
                   return $q->whereName('ماده')
                   ->orWhere('name','اصل');
                });
            }])
            ->select('title', 'id')->get();
        $countUrls = 0;
        foreach ($rules as $rule){
            $path = public_path('/sitemap-'. ($i+1) .'.xml');
            $sitemapI->add(Url::create('/rule/' . replace_space_in_address_bar_with_dash($rule->title) . '/' . $rule->id));
            $countUrls++;
            foreach ($rule->items as $item){

            }
            $i++;
        }
        do {

            $sitemapI = SitemapGenerator::create(config('app.url'))->getSitemap();
            foreach ($rules as $rule) {
            }
            $sitemapI->writeToFile($path);
            $sitemap->add('/sitemap-'. ($i+1) .'.xml');
        } while (count($rules) > 0);
        $sitemap->writeToFile($sitemapPath);


    }
}
