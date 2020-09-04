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
				"is_deleted"	=> ['$ne' => 1],
				"user_id"		=> (int)$this->auth->getData()->id
			],
			"limit"	=> $limit,
			"skip"	=> $skip,
			"sort"	=> [
				"_id" => -1
			]
		]);

		$sources = Transactions::getSourceList($this->lang);
		foreach($query as $value)
		{
			$title = "";
			$data[] = [
				"id"			=> substr((string)$value->_id, -8),
				"amount"		=> [
					"value"		=> round((double)$value->amount, 2),
					//"type"		=> $value->action,
					"text"		=> round((double)$value->amount, 2)." AZN"
				],
				"type"			=> Transactions::getTypeList($this->lang)[$value->type],
				"source"		=> ($sources[$value->source]) ? $sources[$value->source]: ["title" => $value->source],
				"created_at"	=> Transactions::dateFormat($value->created_at, "d/m/Y H:i"),
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