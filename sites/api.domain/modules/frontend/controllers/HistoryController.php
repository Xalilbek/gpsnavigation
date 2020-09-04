<?php
namespace Controllers;

use Models\Alerts;
use Models\History;
use Models\LogsTracking;
use Models\Notifications;
use Models\Objects;
use Models\Cache;

class HistoryController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		ini_set('memory_limit','256M');
		$error      = false;
		$id 		= (int)$this->request->get("id");
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit == 0)
			$limit = 1000;
		if($limit > 1000)
			$limit = 200;

		$object = Objects::findFirst([
			[
				"id"	=> $id,
				"users"	=> (int)$this->auth->getData()->id,
			]
		]);

		$dateFrom 	= strtotime($this->request->get("datefrom"));
		$dateTo 	= strtotime($this->request->get("dateto"));
		if(!$dateFrom || !$dateTo)
		{
			$error = $this->lang->get("dateIntervalWrong", "Date interval is wrong");
		}
		elseif($object)
		{
			$binds = [
				"imei"			=> $object->imei,
				"datetime"		=> [
					'$gt' => Objects::getDate($dateFrom),
					'$lte' => Objects::getDate($dateTo),
				]
			];

			$query		= LogsTracking::find([
				$binds,
				"skip"	=> $skip,
				"limit"	=> $limit,
				"sort"	=> [
					"started_at"	=> -1
				]
			]);
			$data 		= [];
			if(count($query) > 0)
			{
				foreach($query as $value)
				{
					list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
					$data[] = [
						//"id"			=> (int)$value->id,
						"longitude"		=> $lon,
						"latitude"		=> $lat,
						"speed"			=> (string)$value->speed." km/saat",
						"angle"			=> (float)$value->angle,
						"date"			=> Objects::dateFiltered($value->datetime),
					];
				}

				$response = [
					"status" 		=> "success",
					"data"			=> $data,
				];
			}
			else
			{
				$error = $this->lang->get("noInformation", "No information");
			}
		}
		else
		{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
		}


		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}


	public function routesAction()
	{
		ini_set('memory_limit','256M');

		$error      = false;
		$id 		= (int)$this->request->get("id");

		$object = Objects::findFirst([
			[
				"id"	=> $id,
				"users"	=> (int)$this->auth->getData()->id,
			]
		]);

		$dateFrom 	= strtotime($this->request->get("datefrom"));
		$dateTo 	= strtotime($this->request->get("dateto"));
		if(!$dateFrom || !$dateTo)
		{
			$error = $this->lang->get("dateIntervalWrong", "Date interval is wrong");
		}
		elseif($object)
		{
			$binds = [
				"object_id"		=> $object->id,
				"started_at"		=> [
					'$gt' 	=> Objects::getDate($dateFrom),
					'$lte' 	=> Objects::getDate($dateTo),
				],
			];
			$historyQuery = History::find([
				$binds,
				"sort"	=> [
					"_id"	=> -1
				]
			]);

			$historyIds = [];
			foreach($historyQuery as $value)
				$historyIds[] = (string)$value->_id;

			$trackBinds = [
				"history_id"	=> [
					'$in'	=> $historyIds
				],
			];

			$trackQuery		= LogsTracking::find([
				$trackBinds,
				"sort"	=> [
					"_id"	=> -1
				]
			]);
			$trackData 		= [];
			$data 		= [];
			if(count($trackQuery) > 0)
			{
				foreach($trackQuery as $value)
				{
					if($value->action == "move"){
						list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
						$trackData[$value->history_id][] = [
							"longitude"		=> $lon,
							"latitude"		=> $lat,
							"speed"			=> (string)$value->speed." km/saat",
							"angle"			=> (float)$value->angle,
							"date"			=> Objects::dateFiltered($value->datetime),
						];
					}
				}

				foreach($historyQuery as $value)
				{
					$history = [
						"id"			=> (string)$value->_id,
						"type"			=> $value->action == "parking" ? "point": "route",
						"action"		=> $value->action == "move" ? "move": "parking",
						"starttime"		=> History::dateFiltered($value->started_at, "d/m/Y H:i:s"),
						"endtime"		=> ($value->ended_at) ? History::dateFiltered($value->ended_at, "d/m/Y H:i:s"): false,
						"duration"		=> $this->lib->durationToStr($this->lang, $value->duration),
						"distance"		=> round($value->distance/1000, 2)." km",
						"finished"		=> ($value->ended_at) ? true: false,
					];

					if($value->action == "parking")
					{
						list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
						$history["coords"] = [
							"longitude"		=> $lon,
							"latitude"		=> $lat
						];
					}
					else
					{
						$history["tracks"] = count($trackData[(string)$value->_id]) > 0 ? $trackData[(string)$value->_id]: false;
					}
					$data[] = $history;
				}

				$response = [
					"status" 		=> "success",
					"data"			=> $data,
				];
			}
			else
			{
				$error = $this->lang->get("noInformation", "No information");
			}
		}
		else
		{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
		}


		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}



	public function listAction()
	{
		ini_set('memory_limit','256M');

		$error      = false;
		$id 		= (int)$this->request->get("id");

		$object = Objects::findFirst([
			[
				"id"	=> $id,
				"users"	=> (int)$this->auth->getData()->id,
			]
		]);

		$dateFrom 	= strtotime($this->request->get("datefrom"));
		$dateTo 	= strtotime($this->request->get("dateto"));
		if(!$dateFrom || !$dateTo)
		{
			$error = $this->lang->get("dateIntervalWrong", "Date interval is wrong");
		}
		elseif($object)
		{
			$binds = [
				"object_id"		=> $object->id,
				"started_at"		=> [
					'$gt' 	=> Objects::getDate($dateFrom),
					'$lte' 	=> Objects::getDate($dateTo),
				],
			];
			$historyQuery = History::find([
				$binds,
				"sort"	=> [
					"_id"	=> -1
				]
			]);

			$historyIds = [];
			foreach($historyQuery as $value)
				$historyIds[] = (string)$value->_id;


			if(count($historyQuery) > 0)
			{
				foreach($historyQuery as $value)
				{
					$history = [
						"id"			=> (string)$value->_id,
						"type"			=> $value->action == "parking" ? "point": "route",
						"action"		=> $value->action == "move" ? "move": "parking",
						"starttime"		=> History::dateFiltered($value->started_at, "d/m/Y H:i:s"),
						"endtime"		=> ($value->ended_at) ? History::dateFiltered($value->ended_at, "d/m/Y H:i:s"): false,
						"duration"		=> $this->lib->durationToStr($this->lang, $value->duration),
						"distance"		=> round($value->distance/1000, 2)." km",
						"finished"		=> ($value->ended_at) ? true: false,
					];

					if($value->action == "parking")
					{
						list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
						$history["coords"] = [
							"longitude"		=> $lon,
							"latitude"		=> $lat,
						];
					}
					$data[] = $history;
				}

				$response = [
					"status" 		=> "success",
					"data"			=> $data,
				];
			}
			else
			{
				$error = $this->lang->get("noInformation", "No information");
			}
		}
		else
		{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
		}


		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}




	public function coordinatesAction()
	{
		ini_set('memory_limit','256M');
		$error      		= false;
		$history_id 		= (string)$this->request->get("id");
		$notifications 		= [];

		if(strlen($history_id) > 0){
			$query = LogsTracking::find([
				[
					"history_id"	=> $history_id,
				],
				"sort"	=> [
					"unixtime"	=> -1
				],
				//"limit"	=> 100
			]);

			$notQuery = Notifications::find(
				[
					[
						"history_id"	=> $history_id,
					],
				]
			);
			foreach($notQuery as $value){
				$notif = Notifications::filterData($this->lang, $value, Alerts::getTypes($this->lang, true));
				$notif["coordinates"] = Objects::getLonLatFromGeometry($value->geometry);
				$notifications[] = $notif;
			}
		}else{
			$query = [];
		}


		$data = [];
		foreach($query as $value)
		{
			//echo "ok:".$history_id; exit((json_encode($value)));
			list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
			$data[] = [
				$lon, $lat, (int)$value->angle
			];
		}


		if(count($data) > 0)
		{
			$response = [
				"status" 		=> "success",
				"data" 			=> $data,
				"notifications" => $notifications,
			];
		}
		else
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}


}