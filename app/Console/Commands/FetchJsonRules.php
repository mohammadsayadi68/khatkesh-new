<?php

namespace App\Console\Commands;

use App\Common\RulesBot;
use App\Rule;
use Illuminate\Console\Command;

class FetchJsonRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rules:tree-fetch {id?}';

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
        $rules = Rule::query();
        if ($this->argument('id')){
            $rules = $rules->where('id',$this->argument('id'));
        }
        $rules->doesntHave('resource')->select('id', 'main_id')->chunk(500, function ($res) {
            $ids = $res->pluck('main_id')->toArray();
            foreach ($ids as $id)
                RulesBot::getInstance()->storeContentRuleTree($id);
        });
    }
}
