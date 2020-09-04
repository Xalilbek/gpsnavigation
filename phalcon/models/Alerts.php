<?php
namespace Models;

use Lib\MainDB;

class Alerts extends MainDB
{
    public static function getSource(){
        return "alerts";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function filterData($lang, $data, $alertTypes=false)
    {
        $alert = [
            "id"                => (string)$data->_id,
            "title"             => (string)$data->title,
            "type"              => (int)$data->type,
            "object_ids"        => $data->object_ids,
            "group_ids"         => $data->group_ids,
            "geozone_ids"       => $data->geozone_ids,
            "geopoint_ids"      => $data->geopoint_ids,
            "radius"            => (int)$data->radius,
            "speed"             => (int)$data->speed,
            "text"              => (string)$data->text,
        ];

        $alert["type_text"] = $alertTypes[$data->type]["title"];

        return $alert;
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

    public static function getTypes($lang, $withKey=false){
        $list = [
            [
                "id"		=> 1,
                "title"		=> $lang->get("ZoneOut", "When object is out of zone"),
            ],
            [
                "id"		=> 2,
                "title"		=> $lang->get("ZoneIn", "When object is in zone"),
            ],
            [
                "id"		=> 3,
                "title"		=> $lang->get("PointAway", "When object is away from point"),
            ],
            [
                "id"		=> 4,
                "title"		=> $lang->get("PointNear", "When object is near to point"),
            ],
            [
                "id"		=> 5,
                "title"		=> $lang->get("OverSpeedText", "Object is over speed"),
            ],
        ];
        if($withKey){
            $listWithKey = [];
            foreach($list as $value)
                $listWithKey[$value["id"]] = $value;

            //exit(json_encode($listWithKey));
            return $listWithKey;
        }

        return $list;
    }

    public static function getByObjectId($id)
    {
        $groupIds = [];
        $objGroups = ObjectsGroups::find([
            [
                "object_ids" => (int)$id
            ]
        ]);
        if(count($objGroups) > 0)
            foreach($objGroups as $value)
                $groupIds[] = (int)$value->id;

        $binds = [];
        if(count($groupIds) > 0)
        {
            $binds['$or'] = [
                [
                    "object_ids" => (int)$id,
                    "is_deleted" => 0,
                ],
                [
                    "group_ids" => [
                        '$in' => $groupIds
                    ],
                    "is_deleted" => 0,
                ]
            ];
        }
        else
        {
            $binds = [
                "object_ids" => (int)$id,
                "is_deleted" => 0,
            ];
        }

        $query = self::find([
            $binds
        ]);

        //var_dump($binds);exit;

        return count($query) > 0 ? $query: false;
    }
}