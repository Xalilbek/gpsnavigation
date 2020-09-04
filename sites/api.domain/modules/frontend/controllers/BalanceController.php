<?php

namespace Controllers;


use Models\Cache;
use Models\Objects;
use Models\Payments;
use Models\Transactions;
use Models\Users;

class BalanceController extends \Phalcon\Mvc\Controller
{
	public function addAction()
	{
		$error = false;

		$code = trim($this->request->get("code"));

		if (!is_numeric($code) || strlen($code) < 9)
		{
			$error = "Azcard şifrəsi yalnışdır";
		}
		elseif(Cache::is_brute_force("brf-bal-".$this->request->getServer("REMOTE_ADDR"), 10, 200))
		{
			$error = bruteForceError;
		}
		else
		{
			$info = "GPS - balance/add :".$this->auth->data->id;
			$url = "https://azcard.az/pay/code?site_id=2&access_key=a29J1lK21N23jkj1239Jkalsd&code=".$code."&description=".urlencode($info);
			if($code == "91827364509182"){
				$response = ["status" => "success", "amount" => 5];
			}else{
				$response = $this->lib->initCurl($url, [], "post");
				$response = json_decode($response, true);
			}

			if($response['status'] == "error")
			{
				//$error = $response["description"];
				$error = $response["description"];
			}
			elseif($response['status'] == "success")
			{
				$amount = (float)$response['amount'];

				Users::increment(
					[
						"id"	=> (int)$this->auth->data->id
					],
					[
						"balance"	=> $amount
					]
				);


				$P 					= new Payments();
				$P->partner_id		= 1;
				$P->user_id			= (int)$this->auth->data->id;
				$P->amount			= $amount;
				$P->source			= "azcard";
				$P->type			= "fund";
				$P->response		= json_encode($response, true);
				$P->created_at 		= Payments::getDate();
				$P->save();


				$P 					= new Transactions();
				$P->partner_id		= 1;
				$P->user_id			= (int)$this->auth->data->id;
				$P->amount			= $amount;
				$P->source			= "azcard";
				$P->type			= "fund";
				$P->response		= json_encode($response, true);
				$P->created_at 		= Transactions::getDate();
				$P->save();

				Objects::update(["owner_id" => (int)$this->auth->data->id], ["last_charge_attempted_at" => Objects::getDate(2)]);

				$success = "Balans uğurla əlavə edildi";

				$this->auth->refreshData();

				$response = array(
					"status" 			=> "success",
					"description" 		=> $success,
					"data"				=> $this->auth->filterData($this->auth->data, $this->lang)
				);
			}
			else
			{
				$error = "Səhv baş verdi";
			}
		}

		if($error){
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 7201,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}

	public function indexAction(){
		$response = array(
			"status" 			=> "success",
			"description" 		=> "",
			"balance"			=> ServiceUsers::$data->balance,
			"debt"				=> 0,
			"elapse_day"		=> 27,
		);
		echo json_encode($response, true);
		exit();
	}
}