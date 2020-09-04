<?php
namespace Controllers;

use Models\Cache;
use Models\Users;

class UsersController extends \Phalcon\Mvc\Controller
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
			"type"			=> "user",
			"is_deleted"	=> 0,
		];
		
		$sort_field = trim($this->request->get("sort"));
		$sort_order = trim($this->request->get("sort_type"));
		
		$sort = [];
		if(in_array($sort_field, ['id', 'email', 'fullname', 'created_at'])){
			$sort[$sort_field] = $sort_order == 'desc' ? -1 : 1;
		}

		$query		= Users::find([
			$binds,
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> $sort
		]);

		$count = Users::count([
			$binds,
		]);

		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = [
					"id"			=> (int)$value->id,
					"created_at"	=> Users::dateFormat($value->created_at, "Y-m-d H:i:s"),
					"email"			=> (string)$value->email,
					"fullname"		=> $value->fullname,
					"balance"		=> $value->balance, 
				];
			}

			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
				"count"			=> $count,
				"skip"			=> $skip,
				"limit"			=> $limit,
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
		$objectType 	= (int)$this->request->get("type");
		$group_id 		= (int)$this->request->get("group_id");
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
		elseif($group_id > 0 && !ObjectsGroups::findFirst([["id" => (int)$group_id, "is_deleted" => 0, "user_id" => (int)$this->auth->getData()->id]]))
		{
			$error = $this->lang->get("GroupNotFound", "Group doesn't exist");
		}
		else
		{
			$objExist =  Objects::findFirst([["imei" => $imei, "is_deleted"	=> 0]]);

			if($objExist)
			{
				$error = $this->lang->get("ObjectExists", "Object exists");
			}
			else
			{
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
		$error 			= false;
		$id 			= (int)$this->request->get("id");
		$email 			= htmlspecialchars(strtolower($this->request->get("email")));
		$fullname 		= htmlspecialchars($this->request->get("fullname"));
		$password 		= htmlspecialchars($this->request->get("password"));
		$phone			= htmlspecialchars($this->request->get("phone"));
		$country 		= (int)($this->request->get("country"));
		$status 		= (int)($this->request->get("status"));
		
		$data 			= Users::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if(Cache::is_brute_force("ediasdtObj-".$id, ["minute"	=> 100, "hour" => 1000, "day" => 3000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif ($password && (strlen($password) < 6 || strlen($password) > 100))
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif(!$data)
		{
			$error = $this->lang->get("noInformation", "No information found");
		}
		else
		{
			$update = [
				"email"			=> mb_strtolower($email),
				"fullname"		=> $fullname,
				"phone"		    => ( string ) $phone,
				"country"		=> ( int ) $country,
				"status"		=> ( int ) $status,
				"updated_at"	=> Users::getDate()
			];
			
			if($password){
				$update["password"] = $this->lib->generatePassword($password);
			}

			Users::update(["id"	=> (int)$id], $update);

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


	public function updateAction()
	{
		$error 			= false;
		$email 			= htmlspecialchars(strtolower($this->request->get("email")));
		$fullname 		= htmlspecialchars($this->request->get("fullname"));
		$password 		= htmlspecialchars($this->request->get("password"));
		$phone			= htmlspecialchars($this->request->get("phone"));
		$country 		= (int)($this->request->get("country"));
		$status 		= (int)($this->request->get("status"));
		
		$data 			= Users::findFirst([
			[
				"id" 			=> (int)$this->auth->data->id,
				"is_deleted"	=> 0,
			]
		]);

		if(Cache::is_brute_force("ediasdtObj-".$id, ["minute"	=> 100, "hour" => 1000, "day" => 3000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif ($password && (strlen($password) < 6 || strlen($password) > 100))
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif(!$data)
		{
			$error = $this->lang->get("noInformation", "No information found");
		}
		else
		{
			$update = [
				"email"			=> mb_strtolower($email),
				"fullname"		=> $fullname,
				"phone"		    => ( string ) $phone,
				"country"		=> ( int ) $country,
				"status"		=> ( int ) $status,
				"updated_at"	=> Users::getDate()
			];
			
			if($password){
				$update["password"] = $this->lib->generatePassword($password);
				$data = Users::findById($this->auth->data->id);
				$token = $this->auth->createToken($this->request, $data);
			}

			Users::update(["id"	=> (int)$this->auth->data->id], $update);
			
			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
			];
			
			if($token){
				$response['token'] = $token;
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



	public function infoAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$data 		= Users::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("noInformation", "No information found");
		}
		else
		{
			$country = $data->country > 0 ? $this->parameters->getById($this->lang, "object_types", $data->country): false;

			$response = [
				"status" 		=> "success",
				"data" 			=> [
					"id"			=> $id,
					"email"			=> (string)$data->email,
					"fullname"		=> (string)$data->fullname,
					"phone"		=> (string)$data->phone,
					"status"		=> (int)$data->status,
					"country"		=> $country,
					"created_at"	=> Users::dateFormat($data->created_at, "Y-m-d H:i:s"),
					"balance"		=> $data->balance,
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
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$data 		= Users::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("noInformation", "No information found");
		}
		else
		{
			$update = [
				"is_deleted"	=> 1,
				"deleter_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> Users::getDate(),
			];

			Users::update(["id"	=> (int)$id], $update);


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