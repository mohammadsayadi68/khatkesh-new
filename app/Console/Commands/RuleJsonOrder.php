<?php

namespace App\Console\Commands;

use App\Http\Resources\OfflineRuleData;
use App\Rule;
use Illuminate\Console\Command;

class RuleJsonOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rule:json-order {id}';

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
        $dir = public_path('/new-offline-rules/');
        if (!is_dir($dir))
            mkdir($dir);
        $id = $this->argument('id');
        $rule = Rule::with(['strurctures' => function ($query) {
            $query->where('type', '!=', 'interpretation')->whereNull('parent_id')->with('structureable', 'childs');
        }])->findOrFail($id);
        $structures = json_encode((new OfflineRuleData($rule))->resolve(), JSON_UNESCAPED_UNICODE);
        $structures = json_decode($structures,true);
        $data = $structures['data'];
        $structures = $structures['structures'];
        $this->newStructures['structures'] = [];
        $this->renderNewJson($structures, $data, $id . '.json');

    }

    public function renderNewJson($structures, $data, $item)
    {
        $countClause = 0;
        foreach ($structures as $structure) {
            if ($structure['type'] == 'clause') {
                array_push($this->newStructures['structures'], $structure);
                $countClause++;
                continue;
            }
            if (array_key_exists('childs', $structure['data'])) {
                $newStructure = $structure;
                unset($newStructure['data']['childs']);
                array_push($this->newStructures['structures'], $newStructure);
                $this->renderNewJson($structure['data']['childs'], $data, $item);
            };

        }
        $data['countClauses'] = $countClause;
        $this->newStructures['data'] = (object)$data;
        \File::put(public_path('new-offline-rules/' . $item), json_encode($this->newStructures, JSON_UNESCAPED_UNICODE));
    }

}
