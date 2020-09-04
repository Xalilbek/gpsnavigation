<?php
namespace Controllers;

use Models\Alerts;
use Models\Cache;

class AlertsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error      = false;
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit == 0)
			$limit = 50;
		if($limit > 200)
			$limit = 200;

		$binds = [
			"user_id"		=> (int)$this->auth->getData()->id,
			"is_deleted"	=> 0,
		];

		$query		= Alerts::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> [
				"_id"	=> 1
			]
		]);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = Alerts::filterData($this->lang, $value, Alerts::getTypes($this->lang, true));
			}

			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
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




	public function addAction()
	{
		$error 			= false;
		$title			= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));
		$type 			= (int)$this->request->get("type");

		$object_ids = [];
		foreach($this->request->get("object_ids") as $value)
			$object_ids[] = (int)$value;

		$group_ids = [];
		foreach($this->request->get("group_ids") as $value)
			$group_ids[] = (int)$value;

		$geozone_ids = [];
		foreach($this->request->get("geozone_ids") as $value)
			$geozone_ids[] = (string)$value;

		$geopoint_ids = [];
		foreach($this->request->get("geopoint_ids") as $value)
			$geopoint_ids[] = (string)$value;
		$radius 		= (int)$this->request->get("radius");
		$speed 			= (int)$this->request->get("speed");
		$text 			= (string)trim($this->request->get("text"));

		if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 200, "hour" => 500, "day" => 2000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(strlen($title) < 2 || strlen($title) > 100)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 100 characters)");
		}
		elseif($type < 1)
		{
			$error = $this->lang->get("AlertTypeError", "Alert type is wrong");
		}
		elseif(count($object_ids) == 0 && count($group_ids) == 0)
		{
			$error = $this->lang->get("ObjOrGroupError", "Object or group is empty");
		}
		elseif(in_array($type, [1,2,3,4]) && (count($geozone_ids) == 0 && count($geopoint_ids) == 0))
		{
			$error = $this->lang->get("GeozonesEmpty", "Geozones / Geopoints are empty");
		}
		elseif(in_array($type, [3,4]) && $radius < 1)
		{
			$error = $this->lang->get("RadiusIsWrong", "Radius is wrong");
		}
		elseif(in_array($type, [5]) && $speed < 1)
		{
			$error = $this->lang->get("SpeedIsWrong", "Speed is wrong");
		}
		//elseif(strlen($text) < 2 || strlen($text) > 100)
		//{
		//	$error = $this->lang->get("AlertTextError", "Alert text is wrong. (minimum 2 and maximum 100 characters)");
		//}
		else
		{
				$id = (int)Alerts::getNewId();
				$userInsert = [
					"id"				=> $id,
					"title"				=> $title,
					"user_id" 			=> (int)$this->auth->getData()->id,
					"type" 				=> $type,
					"object_ids" 		=> $object_ids,
					"group_ids" 		=> $group_ids,
					"geozone_ids" 		=> $geozone_ids,
					"geopoint_ids" 		=> $geopoint_ids,
					"radius" 			=> $radius,
					"speed" 			=> $speed,
					"text" 				=> $text,
					"is_deleted"		=> 0,
					"created_at"		=> Alerts::getDate()
				];

				Alerts::insert($userInsert);


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
		echo json_encode((object)$response);
		exit;
	}

	public function editAction()
	{
		$error 			= false;
		$id 			= (string)$this->request->get("id");
		$title			= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));
		$type 			= (int)$this->request->get("type");

		$object_ids = [];
		foreach($this->request->get("object_ids") as $value)
			$object_ids[] = (int)$value;

		$group_ids = [];
		foreach($this->request->get("group_ids") as $value)
			$group_ids[] = (int)$value;

		$geozone_ids = [];
		foreach($this->request->get("geozone_ids") as $value)
			$geozone_ids[] = (string)$value;

		$geopoint_ids = [];
		foreach($this->request->get("geopoint_ids") as $value)
			$geopoint_ids[] = (string)$value;
		$radius 		= (int)$this->request->get("radius");
		$speed 			= (int)$this->request->get("speed");
		$text 			= (string)trim($this->request->get("text"));

		if(strlen($id) > 0)
			$data 		= Alerts::findFirst([
				[
					"_id" 			=> Alerts::objectId($id),
					"user_id"		=> (int)$this->auth->getData()->id,
					"is_deleted"	=> 0,
				]
			]);

		if(Cache::is_brute_force("editObj-".$id, ["minute"	=> 30, "hour" => 100, "day" => 300]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("editObj-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 600, "day" => 1500]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$data)
		{
			$error = $this->lang->get("AlertNotFound", "Alert doesn't exist");
		}
		elseif(strlen($title) < 2 || strlen($title) > 100)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 100 characters)");
		}
		elseif($type < 1)
		{
			$error = $this->lang->get("AlertTypeError", "Alert type is wrong");
		}
		elseif(count($object_ids) == 0 && count($group_ids) == 0)
		{
			$error = $this->lang->get("ObjGroupError", "Object is empty");
		}
		elseif(in_array($type, [1,2,3,4]) && (count($geozone_ids) == 0 && count($geopoint_ids) == 0))
		{
			$error = $this->lang->get("GeozonesEmpty", "Geozones / Geopoints are empty");
		}
		elseif(in_array($type, [3,4]) && $radius < 1)
		{
			$error = $this->lang->get("RadiusIsWrong", "Radius is wrong");
		}
		elseif(in_array($type, [5]) && $speed < 1)
		{
			$error = $this->lang->get("SpeedIsWrong", "Speed is wrong");
		}
		//elseif(strlen($text) < 2 || strlen($text) > 100)
		//{
		//	$error = $this->lang->get("AlertTextError", "Alert text is wrong. (minimum 2 and maximum 100 characters)");
		//}
		else
		{
			$update = [
				"title"				=> $title,
				"type" 				=> $type,
				"object_ids" 		=> $object_ids,
				"group_ids" 		=> $group_ids,
				"geozone_ids" 		=> $geozone_ids,
				"geopoint_ids" 		=> $geopoint_ids,
				"radius" 			=> $radius,
				"speed" 			=> $speed,
				"text" 				=> $text,
				"updated_at"		=> Alerts::getDate()
			];
			Alerts::update(["_id" => Alerts::objectId($id)], $update);

			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
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


	public function infoAction()
	{
		$error 		= false;
		$id 		= (string)$this->request->get("id");
		$data 		= Alerts::findFirst([
			[
				"id" 			=> (int)$id,
				"users"			=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$objectType = $this->parameters->getById($this->lang, "object_types", $data->type);

			$obj = Alerts::filterData($this->lang, $data);

			$obj["type"] = ($objectType) ? [
				"type"	=> (int)$data->type,
				"title"	=> $objectType["title"]
			]: [
				"type"	=> (int)$data->type,
				"title"	=> ""
			];

			$response = [
				"status" 		=> "success",
				"data" 			=> $obj
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


	public function deleteAction()
	{
		$error 		= false;
		$id 		= (string)$this->request->get("id");
		if(strlen($id) > 0)
			$data 		= Alerts::findFirst([
				[
					"_id" 			=> Alerts::objectId($id),
					"user_id"		=> (int)$this->auth->getData()->id,
					"is_deleted"	=> 0,
				]
			]);

		if (!$data)
		{
			$error = $this->lang->get("AlertNotFound", "Alert doesn't exist");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleter_at"	=> Alerts::getDate()
			];
			Alerts::update(["_id" 			=> Alerts::objectId($id)], $update);

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