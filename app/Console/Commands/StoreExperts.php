<?php

namespace App\Console\Commands;

use App\Expert;
use App\User;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class StoreExperts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experts:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store experts';

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
        $expertsExel = public_path('exel/Experts.xlsx');
        Excel::filter('chunk')->load($expertsExel)->chunk(200, function ($results) {
            foreach ($results as $result) {
                $phone = $result['tlfn_hmrah'];
                if(!$phone)
                    continue;
                try{
                    $user = User::where('phone', $phone)->first();
                    if (!$user) {
                        $user = new User();
                        $user->name = $result['nam'] . ' ' . $result['nam_khanoadgy'];
                        $user->phone = $result['tlfn_hmrah'];
                        $user->verified = 1;
                        $user->vip = 1;
                        $user->password = $phone;
                        $user->role = \App\Constants\User::ROLE_EXPERT;
                        $user->save();
                    }
                    $expert = Expert::where('user_id', $user->id)->first();
                    if (!$expert) {
                        Expert::create([
                            'user_id' => $user->id,
                            'institute_tel' => $result['tlfn_mossh'],
                            'license_number' => $result['shmarh_proanh'],
                            'address' => $result['aadrs_mossh'],
                            'province_area' => $result['astan_hozh_kdayy'],
                            'city_area' => $result['shhr_hozh_kdayy'],
                            'expire_date' => $result['tarikh_aaatbar_proanh'],
                            'undergraduate_field' => $result['rshth_karshnasy'],
                            'postal_code' => $result['kdpsti'],
                        ]);
                    }

                }catch (\Exception $exception){
                    dump($exception->getMessage());
                }
            }

        });
    }
}
