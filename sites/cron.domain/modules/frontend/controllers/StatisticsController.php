<?php
namespace Controllers;

use Models\History;
use Models\LogsRawTracking;
use Models\LogsTracking;
use Models\LogsUnknownTracking;
use Models\Objects;
use Models\Statistics;

class StatisticsController extends \Phalcon\Mvc\Controller
{
    public function getStatistics($objId, $date)
    {
        $data = Statistics::findFirst([
            [
                "object_id"        => $objId,
                "date"             => $date,
            ]
        ]);
        if(!$data)
        {
            $data               = new Statistics();
            $data->object_id    = $objId;
            $data->datetime     = Statistics::getDate(strtotime($date." 00:00:00"));
            $data->date         = $date;
            $data->created_at   = Statistics::getDate();
            $data->save();

            $data = Statistics::findFirst([
                [
                    "object_id"        => $objId,
                    "date"             => $date,
                ]
            ]);
        }

        return $data;
    }

    public function indexAction()
    {
        /**
        LogsTracking::deleteRaw([
                "created_at"		=> [
                    '$lte' 	=> Objects::getDate(time()-10*24*3600),
                ]
            ]); */
        //Statistics::deleteRaw(["asdsad"		=> null]);
        $phpStart = microtime(true);

        /**
        Objects::update([], ["statistics_at" => Objects::getDate(), "next_statistics_date" => null]); */

        for($i=0;$i<1;$i++)
        {
            $objects = Objects::find([
                [],
                "sort"  => [
                    "next_statistics_date" => 1
                ],
                "limit" => 100,
            ]);

            foreach($objects as $value)
            {
                echo "ID: ".$value->id." ".$value->next_statistics_date."<br/>";

                $date = $value->next_statistics_date;
                //$date = "2019-01-10";
                if(strtotime($date) < 10)
                    $date = Objects::dateFormat($value->created_at, "Y-m-d");

                $calcDate = date("Y-m-d");
                if($date !== $calcDate)
                    $calcDate = $date;



                $dateStart  = strtotime($calcDate." 00:00:00");
                $dateEnd    = strtotime($calcDate." 00:00:00")+24*3600;

                $binds = [
                    "object_id"		=> (int)$value->id,
                    "datetime"		=> [
                        '$gt' 	=> Objects::getDate($dateStart),
                        '$lte' 	=> Objects::getDate($dateEnd),
                    ],
                    "action" => "move",
                ];

                $distance		= (int)LogsTracking::sum("last_distance", $binds);
                $duration		= (int)LogsTracking::sum("last_duration", $binds);

                $stat = $this->getStatistics($value->id, $calcDate);

                Statistics::update(
                    [
                        "_id" => $stat->_id,
                    ],
                    [
                        "distance"      => $distance,
                        "duration"      => $duration,
                        "updated_at"    => Statistics::getDate()
                    ]
                );

                $next_statistics_date = $calcDate;
                if($next_statistics_date !== date("Y-m-d"))
                    $next_statistics_date = date("Y-m-d", strtotime($next_statistics_date." 00:00:00")+24*3600);


                echo "Calc date: ".$calcDate.", ";
                echo "Next date date: ".$next_statistics_date.", ";
                echo "Duration: ".$duration.", ";
                echo "Distance: ".$distance."<hr/>";

                Objects::update(
                    [
                        "_id" => $value->_id,
                    ],
                    [
                        "statistics_at"           => Objects::getDate(),
                        "next_statistics_date"    => $next_statistics_date,
                    ]
                );


                if(microtime(true) - $phpStart > 50)
                    exit;
            }

            //sleep(2);
            if(microtime(true) - $phpStart > 50)
                exit;
        }

        exit;
    }


    public function disAction(){
        $historyDistance = LogsTracking::sum("duration", ["history_id" => "5c2abd7187d2db74e3683fa0"]);
        var_dump($historyDistance);
        exit;
    }
}