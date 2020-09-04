<?php

namespace Controllers;

use Lib\MyMongo;
use Models\Files;
use Models\Parameters;
use Models\TempFiles;
use Models\Users;

class CategoriesController extends \Phalcon\Mvc\Controller
{
	public static $table;

	public function initialize()
	{
		$type = $this->request->get("type");

		self::$table = $this->parameters->setCollection($type, $this->lang);
	}

	public function listAction()
	{
		$error      	= false;

		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit == 0)
			$limit = 50;
		if($limit > 200)
			$limit = 200;


		$binds = [];

		if(strlen($this->request->get("active")) > 0)
			$binds["active"] = (int)$this->request->get("active");

		if (mb_strlen($this->request->get("title")) > 0)
			$binds["titles.".$this->lang->getLang()] = [
				'$regex' => trim(($this->request->get("title"))),
				'$options'  => 'i'
			];


		$binds["is_deleted"] = ['$ne' => 1];
		
		$sort_field = trim($this->request->get("sort"));
		$sort_order = trim($this->request->get("sort_type"));
		
		$sort = [];
		if(in_array($sort_field, ['title', 'active'])){
			$sort[strtr($sort_field, [
				'title' => 'titles.' . $this->lang->getLang() 
			])] = $sort_order == 'desc' ? -1 : 1;
		}

		$query		= self::$table->find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> $sort
		]);

		$count = self::$table->count($binds);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = [
					"id"			=> (int)$value->id,
					"title"			=> ($value->titles->{$this->lang->getLang()}) ? (string)$value->titles->{$this->lang->getLang()}: $value->titles->en,
					//"description"	=> ($value->descriptions->{$this->lang->getLang()}) ? (string)$value->descriptions->{$this->lang->getLang()}: $value->descriptions->en,
					"active"		=> (int)$value->active,
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
			$error = $this->lang->get("noInformation", "Information not found");
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






	public function infoAction()
	{
		$error 		= false;
		$response 	= [];
		$id 		= (int)$this->request->get("id");
		$data 		= self::$table->findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("NoInformation", "Information not found");
		}
		else
		{
			$info = [
				"id"			=> (int)$data->id,
				"titles"		=> $data->titles,
				//"descriptions"	=> ($data->descriptions) ? $data->descriptions: [],
				"active"		=> (int)$data->active,
			];

			$response = array(
				"status" 		=> "success",
				"description" 	=> "",
				"data"			=> $info
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





	public function addAction()
	{
		$error 		= false;
		$response 	= false;

		$type 		= $this->request->get("type");
		$active 	= (int)$this->request->get("active");
		$added 		= false;
		$titles 	= [];
		foreach($this->request->get("titles") as $lang => $value)
		{
			$lang = strtolower($lang);
			$name = trim($value);
			if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
			{
				$titles[$lang] = $name;
				$added = true;
			}
		}

		$descriptions = [];
		foreach($this->request->get("descriptions") as $lang => $value)
		{
			$lang = strtolower($lang);
			$name = trim($value);
			if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
				$descriptions[$lang] = $name;
		}

		if(!$added)
		{
			$error = $this->lang->get("FieldsEmpty", "Fields are empty");
		}
		else
		{
			$selfId = self::$table->getNewId();
			$insert = [
				"id"            		=> $selfId,
				"parent_id"     		=> (int)0,
				"titles"        		=> $titles,
				"descriptions"        	=> $descriptions,
				"active"        		=> $active,
				"is_deleted"    		=> 0,
				"index"         		=> (int)$selfId,
				"default_lang"  		=> _LANG_,
				"slug"          		=> str_replace(" ", "_", strtolower(@$titles["en"])),
				"created_at"    		=> MyMongo::getDate(),
			];

			if($type == "currencies")
			{
				$insert["sign"] 		= htmlspecialchars(substr(trim(strtolower($this->request->get("sign"))),0,5));
			}
			elseif($type == "countries")
			{
				$currencies = [];
				foreach($this->request->get("currencies") as $value)
					$currencies[] = (int)$value;
				$languages = [];
				foreach($this->request->get("languages") as $value)
					$languages[] = (int)$value;
				$dial_codes = [];
				foreach($this->request->get("dial_codes") as $value)
					if($value > 0)
						$dial_codes[] = (int)$value;

				$insert["dial_codes"] 	= $dial_codes;
				$insert["code"] 		= htmlspecialchars(substr(trim(strtolower($this->request->get("code"))),0,5));
			}
			elseif($type == "cities")
			{
				$insert["country"] 		= (int)($this->request->get("country"));
				$insert["post_number"] 	= trim($this->request->get("post_number"));
			}

			self::$table->insert($insert);

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
		$error 		= false;
		$puid		= false;

		$id 		= (int)$this->request->get("id");
		$data      	= self::$table->findFirst([["id" => (int)$id]]);
		if($data)
			$puid = (string)$data->_id;
		if (!$data)
		{
			$error = $this->lang->get("NoInformation", "Information not found");
		}
		else
		{
			$active 	= (int)$this->request->get("active");
			$titles 	= [];
			$added 		= false;
			foreach($this->request->get("titles") as $lang => $value)
			{
				$lang = strtolower($lang);
				$name = trim($value);
				if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
				{
					$titles[$lang] = $name;
					$added = true;
				}
			}

			$descriptions = [];
			foreach($this->request->get("descriptions") as $lang => $value)
			{
				$lang = strtolower($lang);
				$name = trim($value);
				if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
					$descriptions[$lang] = $name;
			}

			if(!$added)
			{
				$error = $this->lang->get("FieldsEmpty", "Fields are empty");
			}
			else
			{
				$update = [
					"titles"        		=> $titles,
					"descriptions"        	=> $descriptions,
					"active"        		=> (int)$active,
					"updated_at"    		=> MyMongo::getDate(),
				];

				if($type == "currencies")
				{
					$update["sign"] 		= (substr(trim(strtolower($this->request->get("code"))),0,5));
				}
				elseif($type == "countries")
				{
					$currencies = [];
					foreach($this->request->get("currencies") as $value)
						$currencies[] = (int)$value;
					$languages = [];
					foreach($this->request->get("languages") as $value)
						$languages[] = (int)$value;
					$dial_codes = [];
					foreach($this->request->get("dial_codes") as $value)
						if($value > 0)
							$dial_codes[] = (int)$value;

					$update["currencies"] 	= $currencies;
					$update["languages"] 	= $languages;
					$update["dial_codes"] 	= $dial_codes;
					$update["code"] 		= htmlspecialchars(substr(trim(strtolower($this->request->get("code"))),0,5));
				}
				elseif($type == "cities")
				{
					$update["country"] 		= (int)($this->request->get("country"));
					$update["post_number"] 	= trim($this->request->get("post_number"));
				}

				self::$table->update(["id" => (int)$id], $update);

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully"),
				);
			}




			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if(count($tempFiles) > 0)
			{
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"user_id"      		=> (int)$id,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
						"for"      			=> "profile",
						"filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Files::insert($document);

					$update = [
						"avatar_id"		=> $value->_id,
						"updated_at"	=> $this->mymongo->getDate()
					];

					self::$table->update(["id" => (int)$id], $update);
				}

				TempFiles::update(["puid" => $puid], ["active"	=> 0]);
			}

			$data = self::$table->findFirst([["id" => (int)$id]]);
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

	public function setindexAction($cat_id)
	{
		$data = self::$table->findFirst([["id" => (int)$cat_id]]);
		if($data){
			$update = [
				"index"         => (int)$this->request->get("index_id"),
				"updated_at"    => MyMongo::getDate(),
			];
			self::$table->update(["id" => (int)$cat_id], $update);
		}
		exit;
	}





	public function deleteAction()
	{
		$error 		= false;
		$response 	= [];
		$id 		= (int)$this->request->get("id");
		$data 		= self::$table->findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("NoInformation", "Information not found");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleter_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> MyMongo::getDate()
			];

			self::$table->update(["id"	=> (int)$id], $update);

			$response = array(
				"status" 		=> "success",
				"description" 	=> $this->lang->get("DeletedSuccessfully", "Deleted successfully"),
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
}