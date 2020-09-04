<?php
namespace Controllers;

use Models\Alerts;
use Models\Countries;
use Models\Currencies;
use Models\Users;

class DataController extends \Phalcon\Mvc\Controller
{
	public function countriesAction()
	{
		$data = $this->parameters->getList($this->lang, "countries");

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function createtokenAction()
	{
		$id 	= trim($this->request->get("id"));

		$binds = [];
		$binds["id"] = (int)$id;

		$data = Users::findFirst([
			$binds
		]);

		$token = $this->auth->createToken($this->request, $data);

		$response = array(
			"status" 		=> "success",
			"description" 	=> "",
			"token" 		=> $token,
		);

		echo json_encode($response, true);
		exit();
	}

	public function currenciesAction()
	{
		$data = [];

		$query = Currencies::find([["active" => 1]]);

		foreach($query as $value)
		{
			$data[] = [
				"id"			=> (int)$value->id,
				"title"			=> strlen(@$value->titles->{$this->lang->getLang()}) > 0 ? @$value->titles->{$this->lang->getLang()}: @$value->titles->en,
				"description"	=> strlen(@$value->descriptions->{$this->lang->getLang()}) > 0 ? @$value->titles->{$this->lang->getLang()}: @$value->descriptions->en,
			];
		}

		$response = [
			"status"	=> "success",
			"data"		=> $data
		];
		echo json_encode($response, true);
		exit();
	}


	public function iconsAction()
	{
		$data = [];
		for($i=1;$i<17;$i++)
			$data[] = [
				"id"	=> $i,
				"url"	=> FILE_URL."/assets/svg/".$i.".svg"
			];

		$response = [
			"status"	=> "success",
			"data"		=> $data
		];
		echo json_encode($response, true);
		exit();
	}


	public function alerttypesAction()
	{
		$data = Alerts::getTypes($this->lang);

		$response = [
			"status"	=> "success",
			"data"		=> $data
		];
		echo json_encode($response, true);
		exit();
	}



	public function languagesAction()
	{
		$data = [];

		foreach($this->lang->langData as $key => $value)
		{
			if(in_array($key, $this->lang->langs))
				$data[] = [
					"short_code"	=> $key,
					"title"			=> $value["name"],
				];
		}

		$response = [
			"status"		=> "success",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function objecttypesAction()
	{

		$data = $this->parameters->getList($this->lang, "object_types");

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function multipleAction()
	{
		$data_types = $this->request->get("data_types");

		$data = [];
		foreach($data_types as $value)
			$data[$value] = $this->parameters->getList($this->lang, $value);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function slideAction()
	{
		$data = [
			[
				"url"			=> FILE_URL.'/assets/slideimages/'.$this->lang->getLang().'/0.jpg',
				"link"			=> '/register',
			],
			[
				"url"			=> FILE_URL.'/assets/slideimages/'.$this->lang->getLang().'/1.jpg',
				"link"			=> '/',
			],
			[
				"url"			=> FILE_URL.'/assets/slideimages/'.$this->lang->getLang().'/2.jpg',
				"link"			=> '/',
			],
		];

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,

		];

		echo json_encode($response, true);
		exit();
	}


	public function informationAction()
	{
		$data = [
			"contact" => [
				"title"			=> $this->lang->get("Contact"),
				"description"	=> "By accessing, continuing to use or navigating throughout this site you accept that we will use certain browser cookies to improve your customer experience with us. Grant only uses cookies which will improve your experience with us and will not interfere with your privacy. Please refer to our ",
				"phones"		=> [
					"+380732112222",
				],
				"whatsapp"	=> [
					"+380732112222",
				],
			],
			"about" => [
				"title"			=> $this->lang->get("About"),
				"description"	=> $this->lang->get("AboutContent"),
			],
			"rules" => [
				"title"			=> $this->lang->get("Rules"),
				"description"	=> "By accessing, continuing to use or navigating throughout this site you accept that we will use certain browser cookies to improve your customer experience with us. Grant.bet only uses cookies which will improve your experience with us and will not interfere with your privacy. Please refer to our ",
			],
		];

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,

		];

		echo json_encode($response, true);
		exit();
	}
}