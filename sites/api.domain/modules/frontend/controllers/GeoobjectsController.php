<?php
namespace Controllers;

use Models\GeoObjects;
use Models\Objects;
use Models\Cache;
use Models\ObjectsGroups;

class GeoobjectsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error      = false;
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		$type 		= (string)$this->request->get("type");
		if($limit == 0)
			$limit = 50;
		if($limit > 200)
			$limit = 200;

		$binds = [
			"user_id"		=> (int)$this->auth->getData()->id,
			"is_deleted"	=> 0,
		];

		if($type == "polygon"){
			$binds["type"] = [
				'$in' => ["circle", "polygon"]
			];
		}elseif(strlen($type) > 0)
			$binds["type"] = $type;

		$query		= GeoObjects::find([
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
				$data[] = [
					"id"			=> (string)$value->_id,
					"type"			=> (string)$value->type,
					"title"			=> (string)$value->title,
					"coordinates"	=> (string)$value->type == "polygon" ? $value->geometry->coordinates[0]: $value->geometry->coordinates,
					"radius"		=> (float)@$value->radius,
					"created_at"	=> GeoObjects::dateFiltered($value->created_at, "Y-m-d H:i:s")
				];
			}

			$response = [
				"status" 		=> "success",
				"data"			=> $data,
			];
		}
		else
		{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
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
		$objectType 	= strtolower($this->request->get("type"));
		$coordinates 	= $this->request->get("coordinates");
		$radius 		= (int)$this->request->get("radius");
		$title			= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));

		if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 900, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		elseif(!in_array($objectType, ["point", "circle", "polygon"]))
		{
			$error = $this->lang->get("ObjectTypeIsWrong", "Object type is wrong");
		}
		elseif($objectType == "circle" && $radius < 1)
		{
			$error = $this->lang->get("RadiusIncorrect", "Radius is wrong");
		}
		else
		{
			$geoJson = GeoObjects::getGeojson($objectType, $coordinates);

			if($geoJson)
			{
				$userInsert = [
					"user_id" 		=> (int)$this->auth->getData()->id,
					"title"			=> $title,
					"type"			=> $objectType,
					"geometry"		=> $geoJson,
					"radius"		=> $radius,
					"is_deleted"	=> 0,
					"created_at"	=> Objects::getDate()
				];

				GeoObjects::insert($userInsert);

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("AddedSuccessfully", "Added successfully"),
				);
			}
			else
			{
				$error = $this->lang->get("CoordinatesIncorrect", "Coordinates are incorrect");
			}
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
		$data 			= false;
		$error 			= false;
		$id 			= (string)$this->request->get("id");
		$objectType 	= strtolower($this->request->get("type"));
		$coordinates 	= $this->request->get("coordinates");
		$radius 		= (int)$this->request->get("radius");
		$title			= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));
		if(strlen($id) > 0)
			$data 		= GeoObjects::findFirst([
				[
					"_id" 			=> GeoObjects::objectId($id),
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
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif(strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		elseif(!in_array($objectType, ["point", "circle", "polygon"]))
		{
			$error = $this->lang->get("ObjectTypeIsWrong", "Object type is wrong");
		}
		elseif($objectType == "circle" && $radius < 1)
		{
			$error = $this->lang->get("RadiusIncorrect", "Radius is wrong");
		}
		else
		{
			$geoJson = GeoObjects::getGeojson($objectType, $coordinates);

			if($geoJson)
			{
				$update = [
					"title"			=> $title,
					"type"			=> $objectType,
					"geometry"		=> $geoJson,
					"radius"		=> $radius,
					"updated_at"	=> Objects::getDate()
				];

				//var_dump($update);exit;
				GeoObjects::update(["_id"	=> $data->_id], $update);


				$response = [
					"status" 		=> "success",
					"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
				];
			}
			else
			{
				$error = $this->lang->get("CoordinatesIncorrect", "Coordinates are incorrect");
			}
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 5317,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}






	public function infoAction()
	{
		$data 		= false;
		$error 		= false;
		$id 		= (string)$this->request->get("id");
		if(strlen($id) > 0)
			$data 		= GeoObjects::findFirst([
				[
					"_id" 			=> GeoObjects::objectId($id),
					"user_id"		=> (int)$this->auth->getData()->id,
					"is_deleted"	=> 0,
				]
			]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$response = [
				"status" 		=> "success",
				"data" 			=> [
					"id"			=> $id,
					"title"			=> (string)$data->title,
					"type"			=> (string)$data->type,
					"coordinates"	=> $data->geometry->coordinates[0],
					"created_at"	=> GeoObjects::dateFormat($data->created_at, "Y-m-d H:i:s")
				]
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
		$data 		= false;
		$error 		= false;
		$id 		= (string)$this->request->get("id");
		if(strlen($id) > 0)
			$data 		= GeoObjects::findFirst([
				[
					"_id" 			=> GeoObjects::objectId($id),
					"user_id"		=> (int)$this->auth->getData()->id,
					"is_deleted"	=> 0,
				]
			]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleter_id"	=> (int)$this->auth->getData()->id,
				"deleter_at"	=> GeoObjects::getDate(),
			];
			GeoObjects::update(["_id"	=> $data->_id], $update);

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
		echo json_encode($response);
		exit;
	}
}