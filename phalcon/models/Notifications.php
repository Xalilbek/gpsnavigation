<?php
namespace Models;

use Lib\MainDB;

class Notifications extends MainDB
{
    public static function getSource(){
        return "notifications";
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



    public static function filterData($lang, $data, $alertTypes=false, $object=false)
    {
        $alert = [
            "type"              => [
                "value"      => (int)$data->alert_type,
                "text"       => $alertTypes[$data->alert_type]["title"],
            ],
            "object"            => ($object) ? [
                "id"        => (int)$data->object_id,
                "title"     => (string)$object->title
            ]: false,
            "speed"             => (int)$data->speed,
            "text"              => "Suret heddi: ".(int)$data->speed,
            "date"              => Notifications::dateFiltered($data->created_at, "d/m/Y H:i:s"),
        ];

        return $alert;
    }
}