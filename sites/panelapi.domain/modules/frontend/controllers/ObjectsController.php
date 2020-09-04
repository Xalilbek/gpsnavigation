<?php
namespace Controllers;

use Models\Objects;
use Models\Cache;
use Models\ObjectsGroups;
use Models\Users;

class ObjectsController extends \Phalcon\Mvc\Controller
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
		
		$binds = [
			"is_deleted" => 0,
		];
		
		$title = trim($this->request->get("title"));
		if (mb_strlen($title) > 0){
			$binds["title"] = [
				'$regex' => $title,
				'$options'  => 'i'
			];
		}
		
		$imei = trim($this->request->get("imei"));
		if (strlen($imei) > 3){
			$binds["imei"] = [
				'$regex' => $imei,
				'$options'  => 'i'
			];
		}
		
		$user_id = trim($this->request->get("user_id"));
		if (is_numeric($user_id)){
			$binds["owner_id"] = (int) $user_id;
		}
		
		$sort_field = trim($this->request->get("sort"));
		$sort_order = trim($this->request->get("sort_type"));
		
		$sort = [];
		if(in_array($sort_field, ['id', 'title', 'imei', 'updated_at'])){
			$sort[$sort_field] = $sort_order == 'desc' ? -1 : 1;
		}
		
		$query		= Objects::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> $sort
		]);

		$data 		= [];
		if(count($query) > 0)
		{
			$objectTypes = $this->parameters->getList($this->lang, "object_types", [], true);

			foreach($query as $value)
			{
				$obj = Objects::filterData($this->lang, $value, $objectTypes);
				$user = Users::getById($value->owner_id);
				if($user && $user->id){
					$obj['user'] = $user->email;
				} else {
					$obj['user'] = 'Deleted';
				}
				$data[] = $obj;
			}

			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
			);
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




	public function listbygroupAction()
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
			//"skip"	=> $skip,
			//"limit"	=> $limit,
			"sort"	=> [
				"_id"	=> -1
			]
		]);

		$objectIds = [];
		$groupsData = [];
		foreach($query as $value)
		{
			$groupsData[$value->id] = $value;
			foreach($value->object_ids as $objId)
				if($objId > 0)
					$objectIds[] = (int)$objId;
		}



		$objQuery = Objects::find([
			[
				"users"			=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
				//"id" => ['$in' => $objectIds]
			]
		]);

		$groupsArr 	= [];
		$objectsData = [];
		foreach($objQuery as $value)
		{
			$objectsData[$value->id] = $value;
			if(!in_array((int)$value->id, $objectIds))
			{
				$groupsArr[0][] = Objects::filterData($this->lang, $value);
			}
		}

		$data 		= [];
		//if(count($query) > 0)
		//{
			foreach($query as $value)
			{
				foreach($value->object_ids as $objId)
				{
					$obj = $objectsData[$objId];
					if(!$groupsArr[(int)$value->id])
						$groupsArr[(int)$value->id] = [];

					if($obj)
					{
						$groupsArr[(int)$value->id][] = Objects::filterData($this->lang, $obj);;
					}
				}
				if(!$groupsArr[(int)$value->id])
					$groupsArr[(int)$value->id] = [];
			}
			asort($groupsArr);

            foreach($groupsArr as $key => $value)
			{
				$data[] = [
					"id"		=> $key == 0 ? "none": $key,
					"title"		=> $key == 0 ? $this->lang->get("Uncategorized"): $groupsData[$key]->title,
					"objects" 	=> $value,
				];
			}

		if(count($data) > 0)
		{
			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
				//"groupsArr" 	=> $groupsArr,
			);
		}else{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
		}
		/**
		}
		else
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}*/

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
		$objectType 	= (int)$this->request->get("type");
		$user_id 		= (int)$this->request->get("user_id");
		$group_id 		= (int)$this->request->get("group_id");
		$phone 			= trim($this->request->get("phone"), " ");
		$icon 			= (int)$this->request->get("icon");
		$imei 			= trim($this->request->get("imei"), " ");
		$title			= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));

		if(Cache::is_brute_force("objAdd-".$imei, ["minute"	=> 20, "hour" => 50, "day" => 100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(!is_numeric($user_id))
		{
			$error = $this->lang->get("UserIsntSelected", "User is not selected");
		}
		elseif(!Users::getById($user_id))
		{
			$error = $this->lang->get("UserNotFound", "User doesn't exist");
		}
		elseif(strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		elseif(strlen($imei) < 6 || strlen($imei) > 40 || !is_numeric($imei))
		{
			$error = $this->lang->get("SerialIdWrong", "IMEI is wrong");
		}
		elseif($objectType < 1)
		{
			$error = $this->lang->get("ObjectTypeIsWrong", "Object type is wrong");
		}
		elseif($icon < 1)
		{
			$error = $this->lang->get("IconWrong", "Please, choose icon");
		}
		elseif($group_id > 0 && !ObjectsGroups::findFirst([["id" => (int)$group_id, "is_deleted" => 0, "user_id" => (int)$this->auth->getData()->id]]))
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		else
		{
			$objExist =  Objects::findFirst([["imei" => $imei]]);

			if($objExist && $objExist->owner_id > 0)
			{
				$error = $this->lang->get("ObjectExists", "Object exists");
			}
			elseif(!$objExist)
			{
				$error = $this->lang->get("DeviceNotRegOnSys", "Device was not registered on system");
			}
			else
			{
				/**
				$id = (int)Objects::getNewId();
				$userInsert = [
					"id"			=> $id,
					"imei"			=> $imei,
					"owner_id" 		=> (int)$this->auth->getData()->id,
					"users" 		=> [(int)$this->auth->getData()->id],
					"title"			=> $title,
					"type"			=> $objectType,
					"group_id"		=> [$group_id],
					"is_deleted"	=> 0,
					"created_at"	=> Objects::getDate()
				];

				Objects::insert($userInsert);
				 */
				$id = $objExist->id;

				Objects::update(
					[
						"_id" => $objExist->_id
					],
					[
						"owner_id"		=> (int)$user_id,
						"users" 		=> [(int)$user_id],
						"title"			=> $title,
						"is_deleted"	=> 0,
						"icon"			=> $icon,
						"type"			=> $objectType,
						"group_id"		=> [$group_id],
						"phone"			=> $phone,
						"owned_at"		=> Objects::getDate()
					]
				);

				if($group_id > 0)
				{
					$groupData = ObjectsGroups::getById($group_id);
					if($groupData && !in_array($id, $groupData->object_ids))
					{
						$groupData->object_ids[] = $id;
						ObjectsGroups::update(
							[
								"id" => (int)$group_id,
							],
							[
								"object_ids"	=> $groupData->object_ids,
								"updated_at"	=> ObjectsGroups::getDate()
							]
						);
					}
				}

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("AddedSuccessfully", "Added successfully"),
				);
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
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$group_id 	= (int)$this->request->get("group_id");
		$phone 		= trim($this->request->get("phone"), " ");
		$imei 		= (int)$this->request->get("imei");
		$icon 		= (int)$this->request->get("icon");
		$objectType = (int)$this->request->get("type");
		$title 		= trim(str_replace(["<",">"], "",trim($this->request->get("title"))));
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
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
		elseif (strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		elseif($icon < 1)
		{
			$error = $this->lang->get("IconWrong", "Please, choose icon");
		}
		elseif($group_id > 0 && !ObjectsGroups::findFirst([["id" => (int)$group_id, "is_deleted" => 0, "user_id" => (int)$this->auth->getData()->id]]))
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		else
		{
			$update = [
				"title"			=> $title,
				"imei"		    => $imei,
				"type"			=> $objectType,
				"icon"			=> $icon,
				"group_id"		=> [$group_id],
				"phone"			=> $phone,
				"updated_at"	=> Objects::getDate()
			];
			Objects::update(["id"	=> (int)$id], $update);


			/**
			$existGroups = ObjectsGroups::find([
				[
					"object_ids" => (int)$id,
				]
			]);
			foreach($existGroups as $value)
			{
				$ids = [];
				foreach($value->object_ids as $oid)
					if((int)$oid !== (int)$id)
						$ids[] = (int)$oid;

				ObjectsGroups::update(
					[
						"_id" => $value->_id,
					],
					[
						"object_ids" => $ids
					]
				);
			}

			if($group_id > 0)
			{
				$groupData = ObjectsGroups::getById($group_id);
				if($groupData && !in_array($id, $groupData->object_ids))
				{
					$groupData->object_ids[] = $id;
					ObjectsGroups::update(
						[
							"id" => (int)$group_id,
						],
						[
							"object_ids"	=> $groupData->object_ids,
							"updated_at"	=> ObjectsGroups::getDate()
						]
					);
				}
			} */

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
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
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

			$obj = Objects::filterData($this->lang, $data);

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
		$id 		= (int)$this->request->get("id");
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
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
				"owner_id"		=> 0,
				"deleter_id"	=> Objects::getDate()
			];
			Objects::update(["id"	=> (int)$id], $update);

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