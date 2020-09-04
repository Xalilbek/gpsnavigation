<?php
namespace Controllers;

use Models\Objects;
use Models\Cache;
use Models\ObjectsGroups;

class ObjectgroupsController extends \Phalcon\Mvc\Controller
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

		$query		= ObjectsGroups::find([
			[
				"user_id"		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			],
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
					"id"			=> (int)$value->id,
					"title"			=> $value->title,
				];
			}

			$response = [
				"status" 		=> "success",
				"data"			=> $data,
			];
		}
		else
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
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

		$objectIds 		= [];
		foreach($this->request->get("object_ids") as $value)
			if((int)$value > 0)
				$objectIds[] = (int)$value;

		if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		else
		{
			$id = (int)ObjectsGroups::getNewId();
			$userInsert = [
				"id"			=> $id,
				"user_id" 		=> (int)$this->auth->getData()->id,
				"title"			=> $title,
				"object_ids"	=> $objectIds,
				"is_deleted"	=> 0,
				"created_at"	=> ObjectsGroups::getDate()
			];

			ObjectsGroups::insert($userInsert);

			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("AddedSuccessfully", "Added successfully"),
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






	public function editAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$title 		= trim(str_replace(["<",">"], "",trim($this->request->get("title"))));

		$objectIds 		= [];
		foreach($this->request->get("object_ids") as $value)
			if((int)$value > 0)
				$objectIds[] = (int)$value;


		$data 		= ObjectsGroups::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
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
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		elseif (strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		else
		{
			$update = [
				"title"			=> $title,
				"object_ids"	=> $objectIds,
				"updated_at"	=> ObjectsGroups::getDate()
			];
			ObjectsGroups::update(["id"	=> (int)$id], $update);

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
		$id 		= (int)$this->request->get("id");
		$data 		= ObjectsGroups::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		else
		{
			$objectIds 	= ($data->object_ids) ? $data->object_ids: [];
			$objects 	= [];
			if(count($objectIds) > 0)
			{
				$objectQuery = Objects::find([
					[
						"id" => [
							'$in' => $objectIds
						],
						"users" 		=> (int)$this->auth->getData()->id,
						"is_deleted"	=> 0,
					]
				]);
				foreach($objectQuery as $value)
					$objects[] = [
						"id"		=> (int)$value->id,
						"title"		=> (string)$value->title,
					];
			}


			$response = [
				"status" 		=> "success",
				"data" 			=> [
					"id"			=> $id,
					"title"			=> (string)$data->title,
					"objects"		=> $objects
				],
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
		$id 		= (int)$this->request->get("id");
		$data 		= ObjectsGroups::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleter_id"	=> ObjectsGroups::getDate()
			];
			ObjectsGroups::update(["id"	=> (int)$id], $update);

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