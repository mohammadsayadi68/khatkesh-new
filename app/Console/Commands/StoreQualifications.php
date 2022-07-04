<?php

namespace App\Console\Commands;

use App\Expert;
use App\User;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class StoreQualifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expert:qualifications';

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
        $path = storage_path('Experts.xlsx');
        Excel::filter('chunk')->load($path)->chunk(10, function ($results) {
           foreach ($results as $item){
                $phone = $item['tlfn_hmrah'];
                $t = $item['tttt'];
                $user = User::wherePhone($phone)->select('id')->first();
                if ($user){
                    Expert::whereUserId($user->id)->update([
                       'qualification'=>$t
                    ]);
                }
           }
        });
    }
}
