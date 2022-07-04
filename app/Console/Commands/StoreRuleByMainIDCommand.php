<?php

namespace App\Console\Commands;

use App\Common\RulesBot;
use Illuminate\Console\Command;

class StoreRuleByMainIDCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rule:main-store {mainId}';

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
        RulesBot::getInstance()->storeRule($this->argument('mainId'));
    }
}
