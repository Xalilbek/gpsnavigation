<?php
namespace Controllers;

use Lib\Translation;
use Models\CouponsTemp;
use Models\Currencies;
use Models\Events;
use Models\Sports;
use Models\Translations;

class SettingsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$sid		= trim($this->request->get("sid"));
		if(strlen($sid) < 2)
			$sid = md5(microtime(true)."-".$this->request->getServer("REMOTE_ADDR"));

		$langs = [];
		foreach($this->lang->langData as $key => $value)
		{
			if(in_array($key, $this->lang->langs))
				$langs[] = [
					"short_code"	=> $key,
					"title"			=> $value["name"],
				];
		}

		$accountData = false;
		if($this->auth->getData())
			$accountData = $this->auth->filterData($this->auth->getData(), $this->lang);



		//$currency = ($accountData) ? @$accountData["currency"]: $currency = Currencies::filterById((int)1, $this->lang);

		//$currencies = Currencies::getList($this->lang);

		$response = [
			"status"				=> "success",
			"lang"					=> [
					"short_code" 		=> $this->lang->getLang(),
					"title" 			=> "Azərbaycan dili",
			],
			"langs"					=> $langs,
			"lang_version"			=> 1,
			"account_data"			=> ($accountData) ? $accountData: false,
		];

		echo json_encode($response, true);
		exit();
	}








	public function translationsAction()
	{
		$data = [];

		$lang 					= trim(strtolower($this->request->get("lang")));
		$language 				= new Translation();
		if($this->request->get("source") == "app"){
			$language->init(6, $lang);
		}else{
			$language->init(5, $lang);
		}

		foreach($language->data as $key => $value)
		{
			$data[$key]	= $value;
		}

		$response = [
			"status"	=> "success",
			"data" 		=> [
				"lang"			=> $language->getLang(),
				"lang_name"		=> $language->langData[$language->getLang()]["name"],
				"lang_version"	=> 1,
				"lang_data"		=> $data,
			],
		];
		echo json_encode($response, true);
		exit();
	}
}