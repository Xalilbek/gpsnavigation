<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Users;

class RegisterController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$error 			= false;

		/**
		 * fullname
		 * password
		 * email
		 */
		$username 		= htmlspecialchars(strtolower($this->request->get("username")));
		$fullname 		= htmlspecialchars($this->request->get("fullname"));
		$password 		= trim($this->request->get("password"));
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$email 			= htmlspecialchars(strtolower($this->request->get("email")));
		$country 		= (int)$this->request->get("country");

		if(Cache::is_brute_force("citizenAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		//elseif (!$this->lib->checkUsername($username))
		//{
		//	$error = $this->lang->get("UsernameError", "Username is incorrect. (minimum 4, maximum 30 characters. Only letters and numbers are allowed");
		//}
		//elseif (Users::getByUsername($username))
		//{
		//	$error = $this->lang->get("UsernameExists", "Username exists");
		//}
		elseif (strlen($fullname) < 1 || strlen($fullname) > 100)
		{
			$error = $this->lang->get("FirstnameError", "Firstname is empty");
		}
		elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$error = $this->lang->get("EmailError", "Email is wrong");
		}
		elseif (Users::findFirst([["email" => $email, "is_deleted" => 0]]))
		{
			$error = $this->lang->get("EmailExists", "Email exists");
		}
		elseif (strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		//elseif (strlen($phone) > 0 && (strlen($phone) > 13 || !is_numeric($phone)))
		//{
		//	$error = $this->lang->get("PhoneError", "Phone is wrong");
		//}
		elseif ($country == 0)
		{
			$error = $this->lang->get("CountryError", "Country is empty");
		}
		else
		{
			$id = Users::getNewId();

			$U 						= new Users();
			$U->id 					= $id;
			$U->partner_id 			= 1;
			$U->username 			= $username;
			$U->fullname 			= $fullname;
			$U->phone 				= $phone;
			$U->password 			= $this->lib->generatePassword($password);
			$U->email 				= $email;
			$U->country 			= $country;
			$U->type 				= "user";
			$U->is_deleted 			= 0;
			$U->status 				= 1;
			$U->created_at 			= $this->mymongo->getDate();
			$U->save();

			$data = Users::getById($id);
			if($data)
			{
				$token 		= $this->auth->createToken($this->request, $data);
				$success 	= $this->lang->get("AddedSuccessfully", "Added successfully");

				$response = [
					"status"		=> "success",
					"description"	=> $success,
					"token"			=> $token,
					"data"			=> $this->auth->filterData($data, $this->lang),
				];
			}
			else
			{
				$error = $this->lang->get("TechnicalError", "Technical error occurred. Please, try agian");
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1101,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}

}