<?php
namespace Models;

use Lib\MainDB;

class Objects extends MainDB
{
    public static function getSource(){
        return "objects";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getNewId()
    {
        $last = self::findFirst(["sort" => ["id" => -1]]);
        if ($last) {
            $id = $last->id + 1;
        } else {
            $id = 1;
        }
        return $id;
    }

    public static function getLonLatFromGeometry($geometry)
    {
        if($geometry->coordinates)
        {
            return [
                (float)$geometry->coordinates[0],
                (float)$geometry->coordinates[1],
            ];
        }
        else
        {
            return [false, false];
        }

    }


    public static function getStatusList($lang)
    {
        return [
            0 => [
                "value" => 0,
                "title" => $lang->get("Stop"),
            ],
            1 => [
                "value" => 1,
                "title" => $lang->get("Active")
            ],
            2 => [
                "value" => 2,
                "title" => $lang->get("BalanceIsInsufficient", "Balance is insufficient")
            ],
        ];
    }

    public static function filterData($lang, $value, $objectTypes=false)
    {
        $status = Objects::toSeconds($value->connected_at) > self::getTime() - ONLINE_TIME ? 1: 0;
        $error = false;
        if($value->status == 2){
            $status = 2;
            $error = $lang->get("BalanceIsInsufficient", "Balance is insufficient");
        }
        list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
        $obj = [
            "id"			=> (int)$value->id,
            "title"			=> $value->title,
            "imei"			=> $value->imei,
            "phone"			=> (string)$value->phone,
            "coords"		=> [
                "lon" => $lon,
                "lat" => $lat
            ],
            "angle"	    => (float)@$value->angle,
            "dasd"	    => (time() - Objects::toSeconds($value->connected_at)) / 60,
            "status"	=> Objects::getStatusList($lang)[$status],
            "lastdate"  => Objects::dateFiltered($value->connected_at, "Y-m-d H:i:s"),
            "lasttime"  => $status == 2 ? $lang->get("BalanceIsInsufficient", "Balance is insufficient"): self::secondsToTime($lang, Objects::toSeconds($value->connected_at)),
            "speed"     => $status > 0 ? $value->speed." km/s": "0 km/s",
            "address"   => ($value->address) ? (string)@$value->address->name: "-",
            "icon"      => $value->icon > 0 ? (int)$value->icon: 1,
            "type"      => strlen($value->type) > 0 ? $value->type: false,
        ];
        if($error)
            $obj["error"] = $error;
        if($objectTypes && $objectTypes[$value->type]){
            /**
            $objectType = $objectTypes[$value->type];
            $obj["type"] = ($objectType) ? [
                "type"	=> (int)$value->type,
                "title"	=> $objectType["title"]
            ]: [
                "type"	=> (int)$value->type,
                "title"	=> ""
            ];*/
        }

        return $obj;
    }


    public static function secondsToTime($lang, $inputSeconds) {
        $seconds = MainDB::getTime() - $inputSeconds;

        if($seconds/60 < 60){
            $minutes = (int)($seconds/60);
        }elseif($seconds/3600 < 24){
            $hours = (int)($seconds/3600);
        }elseif($seconds/86400 < 35){
            $days = (int)($seconds/86400);
        }elseif($seconds/(30*86400) < 13){
            $months = (int)($seconds/86400/30);
        }

        if($seconds < 300){
            $date_text = $lang->get("Online");
        }elseif($minutes > 0 && $minutes < 61){
            $date_text = $minutes." ".$lang->get("minutes", "minutes")." ".$lang->get("ago");
        }elseif($hours > 0 && $hours < 25){
            $date_text = $hours." ".$lang->get("hours", "hours")." ".$lang->get("ago");
        }elseif($days > 0 && $days < 34){
            $date_text = $days." ".$lang->get("days", "days")." ".$lang->get("ago");
        }elseif($months > 0 && $months < 12){
            $date_text = $months." ".$lang->get("months", "month(s)")." ".$lang->get("ago");
        }else{
            $date_text = date("Y-m-d H:i:s", $inputSeconds);
        }
        return trim($date_text);
    }
}