<?php

namespace App\Console\Commands;

use App\Common\RulesBot;
use App\Rule;
use Illuminate\Console\Command;

class FetchRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rules:fetch {type?} {status?} {index?} {--u|update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest rules';
    private $currentRule;

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

        $rulesBot = RulesBot::getInstance();
        $filter = [];
        switch ($this->argument('type')) {
            case 1:
                $filter['_isLaw'] = 'true';
                break;
            case 2:
                $filter['_isRegulation'] = 'true';
                break;
            case 3:
                $filter['_isOpenion'] = 'true';
                break;
        }
        $filter['drpStatus'] = $this->argument('status');
        $rulesBot->setFilter($filter);
        $rulesBot->setTypeAndStatus($this->argument('type'), $this->argument('status'));
        $index = $this->argument('index');
        $first = \App\Rule::query()->where('main_id', '>', '253198')->orderBy('main_id', 'ASC')->first()->main_id;
        $second = Rule::query()->where('main_id', '>', '223198')->orderBy('main_id', 'ASC')->first()->main_id;
        $third = Rule::query()->where('main_id', '>', '253198')->orderBy('main_id', 'ASC')->first()->main_id;
        $fourth = Rule::query()->where('main_id', '>', '161637')->orderBy('main_id', 'ASC')->first()->main_id;

        for ($i=1;$i<9999999;$i++){
            $rulesBot->storeRule(rand(1,300000));
        }

    }


}
