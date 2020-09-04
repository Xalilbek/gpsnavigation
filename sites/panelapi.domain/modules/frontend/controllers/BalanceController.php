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

		$id = (int)$this->request->get("id");
		$amount = (int)$this->request->get("amount");

		if (!is_numeric($amount) || $amount <= 0)
		{
			$error = $this->lang->get("AmountIsWrong", "Amount is wrong");
		}
		elseif(Cache::is_brute_force("brf-bal-".$this->request->getServer("REMOTE_ADDR"), 10, 200))
		{
			$error = bruteForceError;
		}
		else
		{
			$P 				= new Transactions();
			$P->partner_id 	= 1;
			$P->user_id 	= (int)$id;
			$P->amount 		= $amount;
			$P->source 		= "panel";
			$P->type 		= "fund";
			$P->created_at 	= Transactions::getDate();
			$P->save();

			Users::increment(["id" => (int)$id], ["balance" => $amount]);

			Objects::update(["owner_id" => (int)$id], ["last_charge_attempted_at" => Objects::getDate(2)]);

			$success = "Balans uğurla artırıldı";

			$response = array(
				"status" 			=> "success",
				"description" 		=> $success,
			);
		}

		if($error){
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}
	
	public function withdrawAction()
	{
		$error = false;

		$id = (int)$this->request->get("id");
		$amount = (int)$this->request->get("amount");

		if (!is_numeric($amount) || $amount <= 0)
		{
			$error = $this->lang->get("AmountIsWrong", "Amount is wrong");
		}
		elseif(Cache::is_brute_force("brf-bal-".$this->request->getServer("REMOTE_ADDR"), 10, 200))
		{
			$error = bruteForceError;
		}
		else
		{
			$user = Users::getById($id);
			
			if($user->balance >= $amount)
			{
				$P 				= new Transactions();
				$P->partner_id 	= 1;
				$P->user_id 	= (int)$id;
				$P->amount 		= $amount;
				$P->source 		= "panel";
				$P->type 		= "withdraw";
				$P->created_at 	= Transactions::getDate();
				$P->save();

				Users::increment(["id" => (int)$id], ["balance" => -1 * $amount]);

				$success = "Balans uğurla çıxardıldı";

				$response = array(
					"status" 			=> "success",
					"description" 		=> $success,
				);
			} else {
				$error = "Balansda kifayət qədər vəsait yoxdur";
			}
		}

		if($error){
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}
}