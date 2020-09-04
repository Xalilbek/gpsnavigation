<?php
namespace Controllers;

use Models\Cache;
use Models\Users;

class AdminsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error	 = false;
		$skip	 = ( int ) $this->request->get( "skip" );
		$limit	 = ( int ) $this->request->get( "limit" );
		if ( $limit == 0 ){
			$limit	 = 50;
		}
		if ( $limit > 200 ){
			$limit	 = 200;
		}

		$binds = [
			"is_deleted" => 0,
			"type" => [
				'$ne' => 'user'
			]
		];
		
		$level	 = ( string ) $this->request->get( "level" );
		if(in_array($level, ['operator','supervisor','adminstrator'])){
			$binds['type'] = ( string ) $level;
		}

		$query = Users::find( [
			$binds,
			"skip"	 => $skip,
			"limit"	 => $limit,
			"sort"	 => [
				"_id" => -1
			]
		] );

		$count = Users::count( [
			$binds,
		] );

		$data = [];
		if ( count( $query ) > 0 ) {
			foreach ( $query as $value ) {
				$data[] = [
					"id"		 => ( int ) $value->id,
					"created_at" => Users::dateFormat( $value->created_at, "Y-m-d H:i:s" ),
					"level"		 => ( string ) $value->type,
					"username"	 => $value->username,
					"fullname"	 => $value->fullname,
				];
			}

			$response = array (
				"status" => "success",
				"data"	 => $data,
				"count"	 => $count,
				"skip"	 => $skip,
				"limit"	 => $limit,
			);
		} else {
			$error = $this->lang->get( "noInformation", "No information found" );
		}

		if ( $error ) {
			$response = array (
				"status"		 => "error",
				"error_code"	 => 1023,
				"description"	 => $error,
			);
		}
		echo json_encode( $response, true );
		exit();
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

			$response = [
				"status" 		=> "success",
				"data" 			=> [
					"id"			=> $id,
					"fullname"		=> (string)$data->fullname,
					"username"		=> (string)$data->username,
					"password"		=> (string)$data->password,
					"level"			=> (string)$data->type,
					"created_at"	=> Users::dateFormat($data->created_at, "Y-m-d H:i:s"),
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

	public function editAction()
	{
		$error	 = false;
		$id		 = ( int ) $this->request->get( "id" );
		$level	 = trim( $this->request->get( "level" ) );
		$fullname	 = trim( $this->request->get( "fullname" ) );
		$username	 = trim( $this->request->get( "username" ) );
		$password	 = trim( $this->request->get( "password" ) );
		
		$user = Users::getById( $id );

		if ( !$user ) {
			$error = $this->lang->get( "UserNotExists", "User not exists" );
		} else {

			$update = [
				'fullname' => ( string ) $fullname,
				'username' => ( string ) $username,
				'type' => ( string ) $level
			];
			
			if($password){
				$update['password'] = ( string ) $this->lib->generatePassword($password);
			}
			
			Users::update(["id"	=> (int)$id], $update);

			$response = array (
				"status"		 => "success",
				"description"	 => $this->lang->get( "ChangedSuccessfully", "Changed successfully" ),
			);
		}

		if ( $error ) {
			$response = [
				"status"		 => "error",
				"error_code"	 => 1017,
				"description"	 => $error,
			];
		}
		echo json_encode( ( object ) $response );
		exit;
	}

	public function addAction()
	{
		$error	 = false;
		$level	 = trim( $this->request->get( "level" ) );
		$fullname	 = trim( $this->request->get( "fullname" ) );
		$username	 = trim( $this->request->get( "username" ) );
		$password	 = trim( $this->request->get( "password" ) );
		
		$user = Users::findFirst( [
			[
				'username' => ( string ) $username
			]
		] );

		if ( $user ) {
			$error = $this->lang->get( "UsernameExists", "Username is exists" );
		} else {
			$id = Users::getNewId();

			$insert = [
				'id' => ( int ) $id,
				'fullname' => ( string ) $fullname,
				'username' => ( string ) $username,
				'password' => ( string ) $this->lib->generatePassword($password),
				'type' => ( string ) $level,
				'created_at' => Users::getDate(),
				'is_deleted' => 0,
			];
			
			Users::insert($insert);

			$response = array (
				"status"		 => "success",
				"description"	 => $this->lang->get( "AddedSuccessfully", "Added successfully" ),
			);
		}

		if ( $error ) {
			$response = [
				"status"		 => "error",
				"error_code"	 => 1017,
				"description"	 => $error,
			];
		}
		echo json_encode( ( object ) $response );
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