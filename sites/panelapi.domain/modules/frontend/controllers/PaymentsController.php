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
		
		$binds = [];
		
		$user_id = (int)$this->request->get("user_id");
		if(is_numeric($user_id) && $user_id > 0){
			$binds['user_id'] = (int)$user_id;
		}
		
		$type = (string)$this->request->get("type");
		if(in_array($type,['fund','withdraw'])){
			$binds['type'] = (string)$type;
		}
		
		$sort_field = trim($this->request->get("sort"));
		$sort_order = trim($this->request->get("sort_type"));
		
		$sort = [];
		if(in_array($sort_field, ['amount', 'type', 'source', 'created_at'])){
			$sort[$sort_field] = $sort_order == 'desc' ? -1 : 1;
		}

		$query		= Transactions::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> $sort
		]);

		$count = Transactions::count([
			$binds,
		]);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$user = Users::getById($value->user_id);
				$data[] = [
					"id" 			=> (int)$value->id,
					"created_at" 	=> Payments::dateFormat($value->created_at, "Y-m-d H:i:s"),
					"user_id"		=> $user ? $user->id : '0',
					"username" 		=> $user ? $user->fullname . ' ('.$user->id.')' : 'Deleted',
					"amount" 		=> $value->amount,
					"source" 		=> $value->source,
					"type" 			=> $value->type,
				];
			}

			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
				"count"			=> $count,
				"skip"			=> $skip,
				"limit"			=> $limit,
			);
		}
		else
		{
			$error = $this->lang->get("noInformation", "No information found");
		}

		if($error)
		{
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