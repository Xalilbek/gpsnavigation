<?php
namespace Models;

use Lib\MainDB;

class GeoObjects extends MainDB
{
    public static function getSource(){
        return "geo_objects";
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

    public static function getGeojson($type, $coordinates)
    {
        $error = false;
        $geoJson = false;
        if($type == "point" && is_numeric($coordinates[0]) && is_numeric($coordinates[1]))
        {
            $geoJson = [
                "type"	=> "Point",
                "coordinates"	=> [(float)$coordinates[0], (float)$coordinates[1]]
            ];
        }
        elseif($type == "circle" && is_numeric($coordinates[0]) && is_numeric($coordinates[1]))
        {
            $geoJson = [
                "type"	=> "Point",
                "coordinates"	=> [(float)$coordinates[0], (float)$coordinates[1]]
            ];
        }
        elseif($type == "polygon" && count($coordinates) > 2)
        {
            $fCoords = [];
            foreach($coordinates as $value){
                if(is_numeric($value[0]) && is_numeric($value[1])){
                    $fCoords[] = [(float)$value[0], (float)$value[1]];
                }else{
                    $error = true;
                }
            }
            if(!$error)
            {
                if($fCoords[0][0] !== $fCoords[count($fCoords)-1][0] && $fCoords[0][1] !== $fCoords[count($fCoords)-1][1])
                    $fCoords[] = $fCoords[0];
                $geoJson = [
                    "type"			=> "Polygon",
                    "coordinates"	=> [$fCoords]
                ];
            }
        }

        return $geoJson;
    }


    public static function checkPointInGeozones($geozoneIds, $coords)
    {
        return self::findFirst([
            [
                "id"        => ['$in' => $geozoneIds],
                "geometry"	=> [
                    '$geoIntersects'	=> [
                        '$geometry'	=> [
                            "type"          => "Point" ,
                            "coordinates"	=> $coords,
                        ]
                    ]
                ],
            ]
        ]);
    }



    public static function getPointsByIds($ids)
    {
        $points = GeoObjects::find([
            [
                "id" => [
                    '$in' => $ids
                ]
            ]
        ]);

        return $points;
    }
}