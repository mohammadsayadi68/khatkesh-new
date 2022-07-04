<?php

namespace App\Jobs;

use App\CategoryRule;
use App\Common\RulesBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $categoryRule;
    private $ids;

    /**
     * Create a new job instance.
     *
     * @param CategoryRule $categoryRule
     * @param array $ids
     */
    public function __construct(CategoryRule $categoryRule, $ids = [])
    {
        $this->categoryRule = $categoryRule;
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rulesBotInstance = RulesBot::getInstance();
        $rulesBotInstance->setCategory($this->categoryRule);
        if (count($this->ids))
            $rulesBotInstance->storeWithIds($this->ids);
        else
            $rulesBotInstance->storeWithCategory($this->categoryRule);
    }
}
