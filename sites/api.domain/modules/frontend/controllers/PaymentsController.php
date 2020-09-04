<?php
namespace Controllers;

use Models\Cache;
use Models\Transactions;
use Models\Users;
use Models\Payments;

class PaymentsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error      = false;
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		
		if($limit == 0){
			$limit = 50;
		}
		if($limit > 200){
			$limit = 200;
		}
		
		$binds = [
			"user_id"		=> (int)$this->auth->getData()->id,
		];
		
		$type = (string)$this->request->get("type");
		if(in_array($type,['fund','withdraw'])){
			$binds['type'] = (string)$type;
		}

		$query		= Transactions::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> [
				"_id"	=> -1
			]
		]);

		$count = Transactions::count([
			$binds,
		]);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = [
					"id" 			=> (int)$value->id,
					"created_at" 	=> Payments::dateFormat($value->created_at, "Y-m-d H:i:s"),
					"amount" 		=> $value->amount . ' AZN',
					"source" 		=> $value->source,
					"type" 			=> $value->type,
				];
			}

			$response = [
				"status" 		=> "success",
				"data"			=> $data,
				"count"			=> $count,
				"skip"			=> $skip,
				"limit"			=> $limit,
			];
		}
		else
		{
			$error = $this->lang->get("noInformation", "No information found");
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}
}