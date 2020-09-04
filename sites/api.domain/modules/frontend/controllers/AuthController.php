<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Countries;
use Models\ForgetLogs;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class AuthController extends \Phalcon\Mvc\Controller
{
	public function signinAction()
	{
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$username 	= strlen($this->request->get("username")) > 0 ? trim($this->request->get("username")): trim($this->request->get("email"));
		$password 	= trim($this->request->get("password"));

		if(Cache::is_brute_force("authIn-".$phone, ["minute"	=> 20, "hour" => 200, "day" => 510]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else if(Cache::is_brute_force("authIn-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 60, "hour" => 600, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else if(strlen($username) < 2)
		{
			$error = $this->lang->get("LoginError", "Phone or password is wrong");
		}
		else
		{
			$binds = [];
			if(is_int($username))
			{
				$binds["id"] = (int)$username;
			}
			elseif(filter_var($username, FILTER_VALIDATE_EMAIL))
			{
				$binds["email"] = mb_strtolower($username);
			}
			else
			{
				$binds["username"] = strtolower($username);
			}
			$binds["password"] = $this->lib->generatePassword($password);
			$binds["type"] = "user";


			$data = Users::findFirst([
				$binds
			]);

			if ($data)
			{
				$token = $this->auth->createToken($this->request, $data);

				$response = array(
					"status" 		=> "success",
					"description" 	=> "",
					"token" 		=> $token,
					"data"			=> $this->auth->filterData($data, $this->lang),
				);
			}
			else
			{
				$error = $this->lang->get("LoginError", "Phone or password is wrong");
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1001,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}



	public function checkusernameAction()
	{
		$username 	= strtolower(trim($this->request->get("username")));

		if(Cache::is_brute_force("citizenAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$this->lib->checkUsername($username))
		{
			$error = $this->lang->get("UsernameError", "Username is incorrect. (minimum 4, maximum 30 characters. Only letters and numbers are allowed");
		}
		elseif (Users::getByUsername($username))
		{
			$error = $this->lang->get("UsernameExists", "Username exists");
		}
		else
		{
			$response = [
				"status"		=> "success",
				"description"	=> "Username is available",
			];
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1001,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}




	public function recoverstep1Action()
	{
		$error = false;

		$msisdn 	= trim(str_replace(["+"," "], "",trim($this->request->get("dialcode")).trim($this->request->get("number"))));
		$email 		= trim(strtolower($this->request->get("email")));

		if(Cache::is_brute_force("forgot-".$email, ["minute"	=> 10, "hour" => 20, "day" => 40]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("authIn-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 30, "hour" => 250, "day" => 500]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$error = $this->lang->get("EmailError", "Email is wrong");
		}
		else
		{
			$data = Users::findFirst([["email" => $email]]);

			if(!$data)
			{
				$error = $this->lang->get("EmailNotFound", "Email doesnt exists");
			}
			else
			{
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
					$hash = md5($msisdn . "-" . microtime(true));

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

	public function recoverstep2Action()
	{
		$email 			= trim(strtolower($this->request->get("email")));
		$code   		= trim($this->request->get("code"));
		$password   	= trim($this->request->get("password"));
		$repassword   	= trim($this->request->get("repassword"));

		$temp = ForgetLogs::findFirst([
			[
				"email" 		=> $email,
				"status" 		=> 1
			],
			"sort" => [
				"_id"	=> -1
			]
		]);

		if(strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong. (minimum 6 and maximum 40 characters)");
		}
		elseif (!$temp)
		{
			$error = $this->lang->get("VerificaitonCodeWrong", "Verification code is wrong");
		}
		elseif (strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif (strlen($repassword) > 0 && $password !== $repassword)
		{
			$error = $this->lang->get("RePasswordError", "Passwords dont match");
		}
		elseif ($temp->check_limit > 8)
		{
			$error = $this->lang->get("VerificaitonExpired", "Verification code has been expired");
		}
		else
		{
			$temp->check_limit 	+= 1;

			$data = Users::findFirst([["email" => $email]]);
			if((string)$temp->code == (string)$code)
			{
				$temp->delete();

				$data->password	= $this->lib->generatePassword($password);
				$data->save();


				$token 		= $this->auth->createToken($this->request, $data);

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("PasswordUpdated", "Password updated successfuly"),
					"token" 		=> $token,
					"data"			=> $this->auth->filterData($data, $this->lang),
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