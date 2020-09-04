<?php
namespace Controllers;

use Models\LogsTracking;
use Models\Objects;
use Models\Statistics;

class StatisticsController extends \Phalcon\Mvc\Controller
{

	public function intervalAction()
	{
		$error      = false;
		$id 		= (int)$this->request->get("id");

		$object = Objects::findFirst([
			[
				"id"	=> $id,
				"users"	=> (int)$this->auth->getData()->id,
			]
		]);

		$dateFrom 	= strtotime(substr($this->request->get("datefrom"),0,10));
		$dateTo 	= strtotime(substr($this->request->get("dateto"),0,10));
		if(!$dateFrom || !$dateTo)
		{
			$error = $this->lang->get("dateIntervalWrong", "Date interval is wrong");
		}
		elseif($object)
		{

			$binds = [
				"object_id"		=> $object->id,
				"datetime"		=> [
					'$gte' 	=> Objects::getDate($dateFrom),
					'$lte' 	=> Objects::getDate($dateTo),
				]
			];

			$distance		= 0;
			$duration		= 0;

			$statQuery = Statistics::find([
				$binds,
			]);

			foreach($statQuery as $value)
			{
				$duration += $value->duration;
				$distance += $value->distance;
			}

			$response = [
				"status" 		=> "success",
				"data"			=> [
					"distance"		=> [
						"value"		=> round($distance/1000, 2),
						"text"		=> round($distance/1000, 2)." km",
					],
					"duration"		=> [
						"value"	=> $duration,
						"text"	=> $this->lib->durationToStr($this->lang, $duration),
					],
				],
			];
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


	public function dateintervalAction()
	{
		$error      = false;
		foreach($this->request->get("ids") as $value)
			$ids[] = (int)$value;
		$dateFrom 	= strtotime(substr($this->request->get("datefrom"),0,10));
		$dateTo 	= strtotime(substr($this->request->get("dateto"),0,10));

		$objects = [];
		if(count($ids) > 0)
			$objects = Objects::find([
				[
					"id"	=> [
						'$in' => $ids
					],
					"users"	=> (int)$this->auth->getData()->id,
				]
			]);

		$ids = [];
		$objectsData = [];
		foreach($objects as $value)
		{
			$ids[] = (int)$value->id;
			$objectsData[(int)$value->id] = $value;
		}

		if(!$dateFrom || !$dateTo)
		{
			$error = $this->lang->get("dateIntervalWrong", "Date interval is wrong");
		}
		elseif(count($ids) > 0)
		{
			$binds = [
				"object_id"		=> [
					'$in' => $ids
				],
				"datetime"		=> [
					'$gte' 	=> Objects::getDate($dateFrom),
					'$lte' 	=> Objects::getDate($dateTo),
				]
			];

			$statQuery = Statistics::find([
				$binds,
			]);

			$durations = [];
			$distances = [];
			$total = [];

			foreach($statQuery as $value)
			{
				$durations[$value->object_id][$value->date] = $value->duration;
				$distances[$value->object_id][$value->date] = $value->distance;
				$total[$value->object_id]["duration"] += $value->duration;
				$total[$value->object_id]["distance"] += $value->distance;
			}

			$data = [];
			foreach($objects as $value)
			{
				$dates = [];
				for($i=$dateFrom;$i<$dateTo+10;$i = $i + 24*3600)
				{
					$date = date("Y-m-d", $i);
					$dates[$date] = [
						"date"		=> $date,
						"duration"	=> $this->lib->durationToStr($this->lang, (int)$durations[$value->id][$date]),
						"distance"	=> round((int)$distances[$value->id][$date]/1000, 2)." km",
					];
				}

				$data[] = [
					"id"			=> $value->id,
					"title"			=> $value->title,
					"imei"			=> $value->imei,
					"icon"			=> $value->icon,
					"total"			=> [
						"duration" => $this->lib->durationToStr($this->lang, $total[$value->id]["duration"]),
						"distance" => round($total[$value->id]["distance"]/1000, 2)." km",
					],
					"data"			=> $dates
				];
			}


			$response = [
				"status" 		=> "success",
				"data"			=> $data,
			];
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

}