<?php
namespace Controllers;

use Models\Currencies;
use Models\Transactions;

class TransactionsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$data 		= [];
		$limit		= (int)$this->request->get("limit");
		if($limit < 1)
			$limit = 30;
		$skip		= (int)$this->request->get("skip");

		$query = Transactions::find([
			[
				"is_deleted"	=> 0
			],
			"limit"	=> $limit,
			"skip"	=> $skip,
			"sort"	=> [
				"_id" => -1
			]
		]);

		foreach($query as $value)
		{
			$title = "";
			$data[] = [
				"id"			=> substr((string)$value->_id, -8),
				"title"			=> "Paymanat",
				"amount"		=> [
					"value"		=> round((double)$value->amount, 2),
					"type"		=> $value->action,
					"text"		=> round((double)$value->amount, 2)." ".Currencies::filterById((int)$data->currency, $this->lang)["title"]
				],
				"status"		=> [
					"value"		=> (int)$value->status,
					"text"		=> Transactions::getStatusList($this->lang)[(int)$value->status]["title"],
					"color"		=> Transactions::getStatusList($this->lang)[(int)$value->status]["color"],
				],
				"created_at"	=> Currencies::dateFormat($value->created_at, "d/m/Y H:i"),
			];
		}

		$response = [
			"status"	=> "success",
			"data"		=> $data
		];
		echo json_encode($response, true);
		exit();
	}

}