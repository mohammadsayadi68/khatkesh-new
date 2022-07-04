<?php

namespace App\Console\Commands;

use App\Common\RulesBot;
use App\Rule;
use Illuminate\Console\Command;

class FetchRuleImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rules:images-fetch';

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
        Rule::query()->doesntHave('images')->select('id', 'main_id')->chunk(500, function ($res) {
            $ids = $res->pluck('main_id','id')->toArray();
            foreach ($ids as $id=>$mainID)
                RulesBot::getInstance()->storeRuleImages($mainID,$id);
        });
    }
}
