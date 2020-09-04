<?php

namespace Controllers;

use Lib\Lib;

class JobsController extends \Phalcon\Mvc\Controller
{
	public function initAction(){
		function charge($url){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, false) ;
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0) ;
			curl_setopt($ch, CURLOPT_TIMEOUT, 2) ;
			curl_exec($ch);
			curl_close($ch);
			return;
		}
		$charge_urls = [
			'http://crons.utigps.com/filter',
			'http://crons.utigps.com/addresses',
			'http://crons.utigps.com/statistics',
			'http://crons.utigps.com/charges',
		];

		foreach($charge_urls as $url)
		{
			$response = charge($url);
			//echo "######### ".$url.' ##########<br/>';
			echo substr($response,0,20);
			echo "<br/>";
		}
		exit;
	}
}