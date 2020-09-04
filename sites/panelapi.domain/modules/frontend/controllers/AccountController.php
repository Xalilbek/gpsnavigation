<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Countries;
use Models\ForgetLogs;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class AccountController extends \Phalcon\Mvc\Controller
{
	public function infoAction()
	{
		$response = array(
			"status" 		=> "success",
			"description" 	=> "",
			"data"			=> $this->auth->filterData($this->auth->getData(), $this->lang),
		);
		echo json_encode($response);
		exit();
	}




	public function updateAction()
	{
		$error 		= false;

		$fullname 		= trim($this->request->get("fullname"));
		$gender 		= trim($this->request->get("gender")) == "female" ? "female": "male";
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		//$email 			= htmlspecialchars($this->request->get("email"));
		$country 		= (int)$this->request->get("country");

		if(Cache::is_brute_force("infocUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 500, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($fullname) < 1 || strlen($fullname) > 100)
		{
			$error = $this->lang->get("FirstnameError", "Firstname is empty");
		}
		/**
		elseif (strlen($phone) > 0 && (strlen($phone) > 13 || !is_numeric($phone)))
		{
			$error = $this->lang->get("PhoneError", "Phone is wrong");
		}
		elseif ($country == 0)
		{
			$error = $this->lang->get("CountryError", "Country is empty");
		}
		*/
		else
		{
			$update = [
				"fullname"		=> $fullname,
				//"email"			=> $email,
				"phone"			=> $phone,
				"gender"		=> $gender,
				"country"		=> $country,
				"updated_at"	=> $this->mymongo->getDate()
			];

			Users::update(["id" => (int)$this->auth->getData()->id], $update);

			$success = $this->lang->get("UpdatedSuccessfully");

			$data = Users::getById($this->auth->getData()->id);

			$response = [
				"status"		=> "success",
				"description"	=> $success,
				"data"			=> $this->auth->filterData($data, $this->lang),
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1021,
			];
		}

		echo json_encode($response, true);
		exit();
	}






	public function updateemailAction()
	{
		$error 		= false;

		$email 		= trim(strtolower($this->request->get("email")));

		if(Cache::is_brute_force("emailUpdate-".$this->auth->getData()->id, ["minute"	=> 10, "hour" => 30, "day" => 90]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($email) > 0 && !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$error = $this->lang->get("EmailError", "Email is wrong");
		}
		else
		{
			$update = [
				"email_verified"	=> 0,
				"email"				=> $email,
				"updated_at"		=> $this->mymongo->getDate()
			];

			Users::update(["id" => (int)$this->auth->getData()->id], $update);

			$success = $this->lang->get("UpdatedSuccessfully");

			$data = Users::getById($this->auth->getData()->id);

			$response = [
				"status"		=> "success",
				"description"	=> $success,
				"data"			=> $this->auth->filterData($data, $this->lang),
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1021,
			];
		}

		echo json_encode($response, true);
		exit();
	}






	public function passwordAction()
	{
		$error 			= false;

		$oldpassword 	= trim($this->request->get("oldpassword"));
		$password 		= trim($this->request->get("password"));
		$repassword 	= trim($this->request->get("repassword"));

		if(Cache::is_brute_force("infocUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 500, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif ($this->lib->generatePassword($oldpassword) !== $this->auth->getData()->password)
		{
			$error = $this->lang->get("OldPasswordError", "Old password is wrong");
		}
		elseif (strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif (strlen($repassword) > 0 && $password !== $repassword)
		{
			$error = $this->lang->get("RePasswordError", "Passwords dont match");
		}
		else
		{
			$update = [
				"password" 		=> $this->lib->generatePassword($oldpassword),
				"updated_at"	=> $this->mymongo->getDate()
			];

			Users::update(["id" => (int)$this->auth->getData()->id], $update);

			$success = $this->lang->get("UpdatedSuccessfully");

			$response = [
				"status"		=> "success",
				"description"	=> $success,
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1021,
			];
		}

		echo json_encode($response, true);
		exit();
	}




	public function logoutAction()
	{
		$error		= false;
		$token 		= $this->auth->getToken();
		if($token)
		{
			Tokens::update(
				["token" => $token],
				["active" => 0]
			);
		}

		$response = [
			"status"		=> "success",
			"description"	=> $this->lang->get("ExecutedSuccessfully", "Executed Successfully")
		];

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1021,
			];
		}

		echo json_encode($response, true);
		exit();
	}


	public function emailverifystep1Action()
	{
		$error = false;

		$email 		= trim(strtolower($this->request->get("email")));

		if(Cache::is_brute_force("forgot-".$email, ["minute"	=> 10, "hour" => 20, "day" => 40]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("authIn-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 30, "hour" => 250, "day" => 500]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else
		{
			$data = $this->auth->getData();

			if(!$data)
			{
				$error = $this->lang->get("EmailNotFound", "Email doesnt exists");
			}
			else
			{
				$data->email				= $email;
				$data->email_verified		= 0;
				$data->save();

				$L = ForgetLogs::findFirst([
					[
						"email"	=> $email,
						//"msisdn"	=> $msisdn,
						"created_at" => [
							'$gt'	=> time()-24*3600
						]
					],
					"sort"	=> [
						"created_at" => -1
					]
				]);

				if($L && Users::toSeconds(@$L->created_at) < time()-24*3600){
					$L->delete();
					$L = false;
				}

				if(!$L)
				{
					$code = rand(111111, 999999);
					$hash = md5($email . "-" . microtime(true));

					$L				= new ForgetLogs();
					//$L->id 			= ForgetLogs::getNewId();
					$L->code 		= (string)$code;
					$L->hash 		= $hash;
					$L->status 		= 1;
					$L->check_limit = 0;
				}
				else
				{
					$L->check_limit = 0;
				}

				if(@$L->sms_count < 3)
				{
					@$L->sms_count += 1;

					$layout = $this->lang->get("VerificationCode", "Verification Code").': ' . $L->code;
					$mailUrl = EMAIL_DOMAIN;
					$vars = [
						"key"			=> "q1w2e3r4t5aqswdefrgt",
						"from"			=> "info@shahmar.info",
						"to"			=> $email,
						"subject"		=> $this->lang->get("VerificationCode", "Verification Code"),
						"content"		=> $layout,
					];

					$response = $this->lib->initCurl($mailUrl, $vars, "post");
				}

				//$L->msisdn 			= $msisdn;
				$L->email 			= $email;
				$L->created_at 		= Users::getDate();
				$L->save();

				$text = $this->lang->get("VerificationCodeSend", "Verification code was sent to your email");
				$response = [
					"status" => "success",
					//"verify_hash" => (string)$hash,
					"description" => (string)$text
				];
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

	public function emailverifystep2Action()
	{
		$email 			= trim(strtolower($this->request->get("email")));
		$code   		= trim($this->request->get("code"));

		$temp = ForgetLogs::findFirst([
			[
				"email" 		=> $email,
				"status" 		=> 1
			],
			"sort" => [
				"_id"	=> -1
			]
		]);

		if (!$temp)
		{
			$error = $this->lang->get("VerificaitonCodeWrong", "Verification code is wrong");
		}
		elseif ($temp->check_limit > 8)
		{
			$error = $this->lang->get("VerificaitonExpired", "Verification code has been expired");
		}
		else
		{
			$temp->check_limit 	+= 1;

			$data = $this->auth->getData();
			if((string)$temp->code == (string)$code)
			{
				$temp->delete();

				$data->email				= $email;
				$data->email_verified		= 1;
				$data->email_verified_at	= Users::getDate();
				$data->save();


				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("EmailVerifed", "Email address was verified successfuly"),
				);
			}
			else
			{
				$error = $this->lang->get("VerificaitonCodeWrong", "Verification code is wrong");
				$temp->save();
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1401,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}
}