<?php
namespace Controllers;

use Models\Alerts;
use Models\GeoObjects;
use Models\History;
use Models\LogsRawTracking;
use Models\LogsTracking;
use Models\LogsUnknownTracking;
use Models\Notifications;
use Models\Objects;

class FilterController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        //exit;

        $phpStart = microtime(true);
        //if((int)$this->request->getServer("HTTP_IS_ADMIN") < 1) exit;

        for($i=0;$i<50;$i++)
        {
            $trackings = LogsRawTracking::find([
                [
                    "created_at" => [
                        '$lt' => Objects::getDate(time()-40)
                    ],
                ],
                "sort"  => [
                    "unixtime" => 1
                ],
                "limit" => 200,
            ]);

            foreach($trackings as $value)
            {
                if($value->data)
                {
                    $obj        = $value->data;
                    echo "ID: ".$value->_id." - ".$obj->timestamp."<br/>";

                    $timestamp  = $value->unixtime;
                    $km         = (float)explode(" ", trim($obj->speed))[0];
                    $imei       = (string)$obj->imei;
                    $imeiData   = Objects::findFirst([["imei" => $imei]]);

                    $geoJson = [
                        "type"			=> "Point",
                        "coordinates"	=> [
                            (float)$obj->longitude, (float)$obj->latitude
                        ]
                    ];

                    $durationElapse = 0;
                    $lastDuration   = 0;
                    $action = "move";

                    if($imeiData)
                    {
                        if($imeiData->last_history_id){
                            $lastHisotry = History::findById($imeiData->last_history_id);
                        }else{
                            $lastHisotry = $this->getLastHistory($imeiData->id, $timestamp, $imei, $geoJson);
                        }

                        list($lastLon, $lastLat) = Objects::getLonLatFromGeometry($imeiData->geometry);

                        $kmFrom = $this->calcDistance($lastLat, $lastLon, (float)$obj->latitude, (float)$obj->longitude);

                        if($imeiData->connected_at)
                            $durationElapse = $timestamp - Objects::toSeconds($imeiData->connected_at);

                        if($durationElapse > 300){
                            $action = "parking";
                        }elseif($durationElapse < 0){
                            $durationElapse = 0;
                        }else{
                            $lastDuration = $durationElapse;
                        }

                        if($action == "parking"){
                            echo " action changed<hr/>";
                            $historyDistance = LogsTracking::sum("last_distance", ["history_id" => (string)$lastHisotry->_id, "action" => "move"]);
                            //exit("sum ".$historyDistance);
                            History::update(
                                [
                                    "_id"   => Objects::objectId($lastHisotry->_id)
                                ],
                                [
                                    "ended_at"      => $imeiData->connected_at,
                                    "duration"      => (Objects::toSeconds($imeiData->connected_at) - Objects::toSeconds($lastHisotry->started_at)),
                                    "distance"      => $historyDistance,
                                    "geometry_to"   => $geoJson,
                                ]
                            );

                            $lastHisotry                = new History();
                            $lastHisotry->imei          = $imei;
                            $lastHisotry->object_id     = $imeiData->id;
                            $lastHisotry->action        = "parking";
                            $lastHisotry->started_at    = $imeiData->connected_at;
                            $lastHisotry->ended_at      = History::getDate($timestamp);
                            $lastHisotry->created_at    = History::getDate();
                            $lastHisotry->geometry      = $geoJson;
                            $lastHisotry->duration      = abs($timestamp - History::toSeconds($imeiData->connected_at));
                            $lastHisotry->distance      = 0;
                            $lastHisotry->save();

                            $lastHisotry                = new History();
                            $lastHisotry->imei          = $imei;
                            $lastHisotry->object_id     = $imeiData->id;
                            $lastHisotry->action        = "move";
                            $lastHisotry->geometry_from = $geoJson;
                            $lastHisotry->started_at    = History::getDate($timestamp);
                            $lastHisotry->created_at    = History::getDate();
                            $lastHisotry->duration      = 0;
                            $lastHisotry->distance      = 0;
                            $lastHisotry->_id = $lastHisotry->save();
                        }

                        Objects::update(
                            [
                                "imei" => (string)$imei
                            ],
                            [
                                "last_history_id"	=> (string)$lastHisotry->_id,
                                "geometry"		    => $geoJson,
                                "speed"		        => $km,
                                "angle"		        => (float)$obj->angle,
                                "updated_at" 	    => Objects::getDate(),
                                "connected_at" 	    => Objects::getDate($timestamp),
                            ]
                        );
                        $id = (int)$imeiData->id;



                        // ########################### start ALERTS ############################

                        $this->checkAlert($imeiData, $obj, $lastHisotry);

                        // ############################ end ALERTS #############################
                    }
                    else
                    {
                        $id = (int)Objects::getNewId();
                        $lastHisotry = $this->getLastHistory($id, $timestamp, $imei, $geoJson);

                        $O                  = new Objects();
                        $O->id              = $id;
                        $O->imei            = (string)$imei;
                        $O->last_history_id = (string)$lastHisotry->_id;
                        $O->geometry        = $geoJson;
                        $O->speed           = $km;
                        $O->angle           = (float)$obj->angle;
                        $O->is_deleted      = 0;
                        $O->connected_at    = Objects::getDate($timestamp);
                        $O->updated_at      = Objects::getDate();
                        $O->created_at      = Objects::getDate();
                        $O->save();

                        $kmFrom = 0;
                    }

                    $T                  = new LogsTracking();
                    $T->object_id       = $id;
                    $T->imei            = $imei;
                    $T->history_id      = (string)$lastHisotry->_id;
                    $T->action          = $action;
                    $T->geometry        = $geoJson;
                    $T->angle           = (float)$obj->angle;
                    $T->speed           = $km;
                    $T->last_distance   = $kmFrom;
                    $T->last_duration   = $lastDuration;
                    $T->duration        = $durationElapse;
                    $T->_test = [
                        "raw_ts"            => Objects::getDate($timestamp),
                        "ts"                => Objects::getDate(),
                        "created_at"        => $value->created_at,
                    ];
                    $T->datetime        = Objects::getDate($timestamp);
                    $logId              = (string)$T->save();


                    LogsRawTracking::deleteRaw(["_id" => $value->_id]);
                }
                else
                {
                    LogsRawTracking::deleteRaw(["_id" => $value->_id]);

                    LogsUnknownTracking::insert([
                        "json"          => $value->json,
                        "created_at" 	=> LogsUnknownTracking::getDate(),
                    ]);
                }

                if(microtime(true) - $phpStart > 50)
                    exit;
            }

            sleep(1);
            if(microtime(true) - $phpStart > 50)
                exit;
        }

        exit;
    }








    public function checkAlert($obj, $trackData, $hisotry)
    {
        $coords = [(float)$trackData->longitude, (float)$trackData->latitude];
        $alerts = Alerts::getByObjectId($obj->id);

        $geoJson = [
            "type"			=> "Point",
            "coordinates"	=> [
                (float)$trackData->longitude, (float)$trackData->latitude
            ]
        ];
        if($alerts)
        {
            echo(count($alerts)." alert found for ".$obj->title."<br/>");
            foreach($alerts as $alert)
            {
                switch((int)$alert->type){
                    case 1:
                        $nowIn = (GeoObjects::checkPointInGeozones($alert->geozone_ids, $coords)) ? true: false;
                        if(!$nowIn)
                        {
                            $prevIn = (GeoObjects::checkPointInGeozones($alert->geozone_ids, $obj->geometry->coordinates)) ? true: false;
                            if($prevIn){
                                $N              = new Notifications();
                                $N->users       = (int)$obj->users;
                                $N->object_id   = (int)$obj->id;
                                $N->alert_id    = (string)$alert->_id;
                                $N->alert_type  = (int)$alert->type;
                                $N->history_id  = (string)$hisotry->_id;
                                $N->geometry    = $geoJson;
                                $N->text        = (string)$alert->text;
                                $N->datetime    = Notifications::getDate($trackData->unixtime);
                                $N->created_at  = Notifications::getDate();
                                $N->save();

                                echo "<font style='color: red;'>ALERT - GEOZONE OUT ###########################</font><br/>";
                            }
                        }

                        break;

                    case 2:
                        $nowIn = (GeoObjects::checkPointInGeozones($alert->geozone_ids, $coords)) ? true: false;
                        if($nowIn)
                        {
                            $prevIn = (GeoObjects::checkPointInGeozones($alert->geozone_ids, $obj->geometry->coordinates)) ? true: false;
                            if(!$prevIn){
                                $N              = new Notifications();
                                $N->users       = (int)$obj->users;
                                $N->object_id   = (int)$obj->id;
                                $N->alert_id    = (string)$alert->_id;
                                $N->alert_type  = (int)$alert->type;
                                $N->geometry    = $geoJson;
                                $N->history_id  = (string)$hisotry->_id;
                                $N->text        = (string)$alert->text;
                                $N->datetime    = Notifications::getDate($trackData->unixtime);
                                $N->created_at  = Notifications::getDate();
                                $N->save();

                                echo "<font style='color: red;'>ALERT - GEOZONE IN ###########################</font><br/>";
                            }
                        }

                        break;

                    case 3:
                        $points = GeoObjects::getPointsByIds($alert->geopoint_ids);
                        if(count($points) > 0)
                        {
                            foreach($points as $point)
                            {
                                $nowDis = $this->calcDistance((float)$trackData->longitude, (float)$trackData->latitude, (float)$point->geometry->coordinates[0], (float)$point->geometry->coordinates[1]);
                                if($point->radius <= $nowDis){
                                    $prevDis = $this->calcDistance((float)$trackData->longitude, (float)$trackData->latitude, (float)$obj->geometry->coordinates[0], (float)$obj->geometry->coordinates[1]);
                                    if($point->radius > $prevDis){
                                        $N              = new Notifications();
                                        $N->users       = (int)$obj->users;
                                        $N->object_id   = (int)$obj->id;
                                        $N->alert_id    = (string)$alert->_id;
                                        $N->alert_type  = (int)$alert->type;
                                        $N->history_id  = (string)$hisotry->_id;
                                        $N->geometry    = $geoJson;
                                        $N->text        = (string)$alert->text;
                                        $N->datetime    = Notifications::getDate($trackData->unixtime);
                                        $N->created_at  = Notifications::getDate();
                                        $N->save();

                                        echo "<font style='color: red;'>ALERT - AWAY FROM POINT ###########################</font><br/>";
                                    }
                                }
                            }
                        }
                        break;

                    case 4:
                        $points = GeoObjects::getPointsByIds($alert->geopoint_ids);
                        if(count($points) > 0)
                        {
                            foreach($points as $point)
                            {
                                $nowDis = $this->calcDistance((float)$trackData->longitude, (float)$trackData->latitude, (float)$point->geometry->coordinates[0], (float)$point->geometry->coordinates[1]);
                                if($point->radius >= $nowDis){
                                    $prevDis = $this->calcDistance((float)$trackData->longitude, (float)$trackData->latitude, (float)$obj->geometry->coordinates[0], (float)$obj->geometry->coordinates[1]);
                                    if($point->radius < $prevDis){
                                        $N              = new Notifications();
                                        $N->users       = (int)$obj->users;
                                        $N->object_id   = (int)$obj->id;
                                        $N->alert_id    = (string)$alert->_id;
                                        $N->alert_type  = (int)$alert->type;
                                        $N->history_id  = (string)$hisotry->_id;
                                        $N->geometry    = $geoJson;
                                        $N->text        = (string)$alert->text;
                                        $N->datetime    = Notifications::getDate($trackData->unixtime);
                                        $N->created_at  = Notifications::getDate();
                                        $N->save();

                                        echo "<font style='color: red;'>ALERT - NEAR TO POINT ###########################</font><br/>";
                                    }
                                }
                            }
                        }
                        break;

                    case 5:
                        if($obj->speed < $alert->speed && $trackData->speed >= $alert->speed){
                            $N              = new Notifications();
                            $N->users       = (int)$obj->users;
                            $N->object_id   = (int)$obj->id;
                            $N->alert_id    = (string)$alert->_id;
                            $N->alert_type  = (int)$alert->type;
                            $N->speed       = (int)$trackData->speed;
                            $N->history_id  = (string)$hisotry->_id;
                            $N->geometry    = $geoJson;
                            $N->text        = (string)$alert->text;
                            $N->datetime    = Notifications::getDate($trackData->unixtime);
                            $N->created_at  = Notifications::getDate();
                            $N->save();

                            echo "<font style='color: red;'> ALERT - OVER SPEED ###########################</font><br/>";
                        }
                        break;
                }
            }
        }
        else
        {

        }
    }





    public function calcDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000 )
    {
        $latFrom = deg2rad( $latitudeFrom );
        $lonFrom = deg2rad( $longitudeFrom );
        $latTo   = deg2rad( $latitudeTo );
        $lonTo   = deg2rad( $longitudeTo );

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin( sqrt( pow( sin( $latDelta / 2 ), 2 ) +
                cos( $latFrom ) * cos( $latTo ) * pow( sin( $lonDelta / 2 ), 2 ) ) );
        return $angle * $earthRadius;
    }




    public function getLastHistory($objId, $timestamp, $imei, $geoJson)
    {
        $data = History::findFirst([
            [
                "object_id" => (int)$objId,
            ],
            "sort"  => [
                "_id" => -1,
            ],
            "limit" => 1,
        ]);
        if(!$data)
        {
            $data                   = new History();
            $data->object_id        = $objId;
            $data->imei             = $imei;
            $data->action           = "move";
            $data->geometry_from    = $geoJson;
            $data->started_at       = History::getDate($timestamp);
            $data->created_at       = History::getDate();
            $data->save();
        }

        return $data;
    }




    public function disAction(){
        $historyDistance = LogsTracking::sum("duration", ["history_id" => "5c2abd7187d2db74e3683fa0"]);
        var_dump($historyDistance);
        exit;
    }
}