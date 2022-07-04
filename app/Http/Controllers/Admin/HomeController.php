<?php

namespace App\Http\Controllers\Admin;

use App\Expert;
use App\Lawyer;
use App\Rule;
use App\Transaction;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
//		$this->middleware('auth');
	}


	public $newStructures = [
		'data' => [],
		'structures' => []
	];

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function index()
	{
		$usersCount = User::whereNotNull('phone_verified_at')->count();
		$countPayments = Transaction::where('status','SUCCEED')->count();
		$sumPayments = Transaction::where('status','SUCCEED')->sum('price');
		$rulesCount = Rule::count();

		return view('admin.home', compact('rulesCount', 'usersCount','countPayments','sumPayments'));
	}


	public function jsonOrder()
	{
		$list = scandir(public_path('json'));

		foreach ($list as $item) {
			if (strpos($item, '.json')) {
				$structures = file_get_contents(public_path() . '/json/' . $item);
				$structures = json_decode($structures, true);
				$data = $structures['data'];
				$structures = $structures['structures'];
				$this->newStructures['structures'] = [];
				$this->renderNewJson($structures, $data, $item);
			}
		}
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
		$fp = fopen(public_path('json/' . $item), 'w');
		fwrite($fp, json_encode($this->newStructures, JSON_UNESCAPED_UNICODE));
		fclose($fp);

	}


	public function insertDataFromExel()
	{
	    ini_set('max_execution_time',-1);
        $lawyersExel = public_path('exel/Lawyers.xlsx');
        $expertsExel = public_path('exel/Experts.xlsx');
		Excel::filter('chunk')->load($expertsExel)->chunk(200, function ($results) {
			foreach ($results as $result) {
				$phone = $result['tlfn_hmrah'];

				$user = User::where('phone', $phone)->first();
				if (!$user) {
					$user = new User();
					$user->name = $result['nam'] . ' ' . $result['nam_khanoadgy'];
					$user->phone = $result['tlfn_hmrah'];
					$user->verified = 1;
					$user->vip = 1;
					$user->password = $phone;
					$user->role = 3;
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
                            $user->role = 3;
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

		Excel::filter('chunk')->load($lawyersExel)->chunk(200, function ($results) {
			foreach ($results as $result) {
				$phone = $result['tlfn_hmrah'];
				if(!$phone)
				    continue;
				$user = User::where('phone', $phone)->first();
				if (!$user) {
					$user = new User();
					$user->name = $result['nam'] . ' ' . $result['nam_khanoadgy'];
					$user->phone = $result['tlfn_hmrah'];
					$user->verified = 1;
					$user->vip = 1;
					$user->password = $phone;
					$user->role = 3;
					$user->save();
				}

				$lawyer = Lawyer::where('user_id', $user->id)->first();
				if (!$lawyer) {
					Lawyer::create([
						'user_id' => $user->id,
						'institute_tel' => $result['tlfn_mossh'],
						'license_number' => $result['shmarh_proanh'],
						'address' => $result['aadrs_mossh'],
						'province_area' => $result['astan_hozh_kdayy'],
						'city_area' => $result['shhr_hozh_kdayy'],
						'expire_date' => $result['tarikh_aaatbar_proanh'],
						'grade' => $result['paih'],
						'postal_code' => $result['kdpsti'],
					]);
				}

			}

		});
	}
}
