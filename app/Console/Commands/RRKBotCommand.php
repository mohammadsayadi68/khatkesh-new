<?php

namespace App\Console\Commands;

use App\Common\RRKBot;
use Illuminate\Console\Command;

class RRKBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rrk-bot:fetch {category}';

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
        RRKBot::getInstance()->crawl($this->argument('category'));
    }
}
