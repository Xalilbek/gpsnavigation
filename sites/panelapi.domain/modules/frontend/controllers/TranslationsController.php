<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Translations;
use Models\Cache;

class TranslationsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error      	= false;

		$lang_from 		= trim($this->request->get("lang_from"));
		if(!in_array($lang_from, $this->lang->testLangs))
			$lang_from = "en";

		$lang_to 		= trim($this->request->get("lang_to"));
		if(!in_array($lang_to, $this->lang->testLangs))
			$lang_to = "az";

		$key 			= trim(str_replace(" ", "", $this->request->get("key")));

		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit == 0)
			$limit = 50;
		if($limit > 200)
			$limit = 200;


		$binds = [];

		if($this->request->get("template") > 0)
			$binds["template_id"] = (int)$this->request->get("template");

		if (mb_strlen($key) > 0)
			$binds["key"] = [
				'$regex' => trim(($this->request->get("key"))),
				'$options'  => 'i'
			];

		if (mb_strlen($this->request->get("lang_from_search")) > 0)
			$binds["translations.".$lang_from] = [
				'$regex' => trim(($this->request->get("lang_from_search"))),
				'$options'  => 'i'
			];

		if (mb_strlen($this->request->get("lang_to_search")) > 0)
			$binds["translations.".$lang_to] = [
				'$regex' => trim(($this->request->get("lang_to_search"))),
				'$options'  => 'i'
			];

		$binds["is_deleted"] = ['$ne' => 1];
		
		$sort_field = trim($this->request->get("sort"));
		$sort_order = trim($this->request->get("sort_type"));
		
		$sort = [];
		if(in_array($sort_field, ['key', 'lang_from', 'lang_to', 'template', 'created_at'])){
			$sort[strtr($sort_field, [
				'lang_from' => 'translations.' . $lang_from, 
				'lang_to' => 'translations.' . $lang_to,
			])] = $sort_order == 'desc' ? -1 : 1;
		}

		$query		= Translations::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> $sort
		]);

		$count = Translations::count([$binds]);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = [
					"id"			=> (string)$value->_id,
					"key"			=> (string)$value->key,
					"template"		=> $value->template_id,
					"lang_from"		=> (string)$value->translations->{$lang_from},
					"lang_to"		=> (string)$value->translations->{$lang_to},
				];
			}

			$response = [
				"status" 		=> "success",
				"limit"			=> $limit,
				"skip"			=> $skip,
				"count"			=> (int)$count,
				"data"			=> $data,
			];
		}
		else
		{
			$error = $this->lang->get("TranslationNotFound", "Translation not found");
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
				"count"			=> (int)$count,
			);
		}
		echo json_encode($response, true);
		exit();
	}


	public function addAction()
	{
		$error 			= false;
		$response		= [];

		$langkey 			= trim(str_replace(" ", "", $this->request->get("key")));

		$templates = [];
		foreach($this->request->get("template") as $value)
			if($value > 0)
				$templates[] = (int)$value;

		$translations = [];
		foreach($this->request->get("translations") as $key => $value)
			if(mb_strlen($value) > 0 && in_array($key, $this->lang->testLangs))
				$translations[$key] = (string)$value;

		if(Cache::is_brute_force("trAdd-".$langkey, ["minute"	=> 20, "hour" => 50, "day" => 100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (mb_strlen($key) == 0)
		{
			$error = $this->lang->get("KeyIsEmpty", "Key is empty");
		}
		elseif (count($templates) == 0)
		{
			$error = $this->lang->get("TemplateIsEmpty", "Template is empty");
		}
		elseif (count($translations) == 0)
		{
			$error = $this->lang->get("TranslationIsEmpty", "Translation is empty");
		}
		elseif ($exist=Translations::findFirst([["key" => $langkey]]))
		{
			$error = $this->lang->get("KeyExists", "Key exists");
		}
		else
		{
			$Insert = [
				"key"			=> $langkey,
				"template_id"	=> $templates,
				"translations"	=> $translations,
				"creator_id" 	=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
				"created_at"	=> Translations::getDate()
			];

			Translations::insert($Insert);

			$response = array(
				"status" 		=> "success",
				"description" 	=> $this->lang->get("AddedSuccessfully", "Added successfully"),
			);
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode($response);
		exit;
	}






	public function editAction()
	{
		$error 			= false;
		$data 			= false;
		$response		= [];

		$id 			= trim($this->request->get("id"));
		$langkey 		= trim(str_replace(" ", "", $this->request->get("key")));

		$templates = [];
		foreach($this->request->get("template") as $value)
			if($value > 0)
				$templates[] = (int)$value;

		$translations = [];
		foreach($this->request->get("translations") as $key => $value)
			if(mb_strlen($value) > 0 && in_array($key, $this->lang->testLangs))
				$translations[$key] = (string)$value;


		if(strlen($id) > 5)
			$data = Translations::findById($id);

		if(!$data)
		{
			$error = $this->lang->get("TranslationNotFound", "Translation not found");
		}
		elseif(Cache::is_brute_force("trEdit-".$langkey, ["minute"	=> 20, "hour" => 50, "day" => 100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (count($templates) == 0)
		{
			$error = $this->lang->get("TemplateIsEmpty", "Template is empty");
		}
		elseif (count($translations) == 0)
		{
			$error = $this->lang->get("TranslationIsEmpty", "Translation is empty");
		}
		else
		{
			foreach($data->translations as $key => $value)
				if(!$translations[$key])
					$translations[$key] = $value;

			$update = [
				"template_id"	=> $templates,
				"translations"	=> $translations,
			];

			Translations::update(
				[
					"_id" => $data->_id
				],
				$update
			);

			$response = array(
				"status" 		=> "success",
				"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully"),
			);
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode($response);
		exit;
	}






	public function deleteAction()
	{
		$error 		= false;
		$data 		= false;
		$id 		= (string)$this->request->get("id");

		if(strlen($id) > 5)
			$data = Translations::findById($id);

		if (!$data)
		{
			$error = $this->lang->get("TranslationNotFound", "Translation not found");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_at"	=> Translations::getDate()
			];
			Translations::update([
				"_id"	=> $data->_id],
				$update
			);

			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("DeletedSuccessfully", "Deleted successfully")
			];
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}
}