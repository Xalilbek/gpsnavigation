<?php
namespace Models;

use Lib\Lib;
use Lib\MainDB;

class Address extends MainDB
{
    public static function getSource(){
        return "addresses";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }


    public static function getByCoords($latitude, $longitude){
        $cacheKey 	= md5("sxx_".$latitude."-".$longitude);
        $data 		= Cache::get($cacheKey);
        if(!$data)
        {
            $url = PLACES_API_URL."/geocode";
            $vars = [
                "key"       => PLACES_API_KEY,
                "lon"     	=> $longitude,
                "lat"       => $latitude,
            ];

            $data 			= Lib::initCurl($url, $vars, "GET");
            $data 			= json_decode($data, true);
            if($data)
                Cache::set($cacheKey, $data, time()+20*24*3600);
        }

        //var_dump($data);exit;

        $location = false;
        if($data)
        {
            $title = $data["title"];
            $location = [
                "name"		=> $title,
                "state" 	=> $data["city"],
                "place_id" 	=> false, //(string)$value["_id"],
                "location"  => [
                    "latitude"  => (float)$latitude,
                    "longitude" => (float)$longitude,
                ],
            ];
        }

        return $location;
    }
}