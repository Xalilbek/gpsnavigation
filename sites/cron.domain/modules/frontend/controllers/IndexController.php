<?php
namespace Controllers;

use Models\Imeis;
use Models\LogsRawTracking;
use Models\LogsUnknownTracking;
use Models\Objects;
use Models\Users;
use Lib\MyMongo;

class IndexController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$json = urldecode($this->request->get("json"));
		$obj = json_decode($json, TRUE);

		if(strlen($obj["ipAddress"]) > 0)
		{
            if($obj["network"] == "azercell"){
                $unixtime = strtotime($obj["timestamp"]) - 4*3600;
            }elseif($obj["protocol"] == "ruptela"){
                $unixtime = strtotime($obj["timestamp"]) - 3*3600;
            }else{
                $unixtime = strtotime($obj["timestamp"]) - 8 * 3600;
            }
			$imei = ($obj["imei"]) ? $obj["imei"]: $obj["deviceId"];
			$insert = [
				"data"			=> $obj,
				//"timestamp"		=> date("Y-m-d H:i:s", strtotime($obj["timestamp"])-4*3600),
				"unixtime"		=> $unixtime,
				"created_at" 	=> MyMongo::getDate(),
			];
			LogsRawTracking::insert($insert);
		}elseif(strlen($json) > 1){
			LogsUnknownTracking::insert([
				"json" => $json,
				"created_at" 	=> MyMongo::getDate(),
			]);
		}
		exit("okk");
	}


	public function statusAction()
	{
		$imei 	= trim($this->request->get("imei"));
		$status = (int)($this->request->get("status"));

		exit($imei."-".$status);
	}
}