<?php
namespace Models;

use Lib\MainDB;
use Lib\Parameters;

class Countries extends MainDB
{
    public static function getSource()
    {
        return "countries";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getList($lang, $filters=[], $cache=false)
    {
        $data       = [];
        $countries  = self::find();
        foreach($countries as $value)
        {
            $data[$value->id] = [
                "title"     => strlen($value->titles->{$lang->getLang()}) > 0 ? $value->titles->{$lang->getLang()}: $value->titles->en,
            ];
        }

        return $data;
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


    public static function filterCountry($id, $lang)
    {
        if($id > 0)
        {
            $table = new Parameters();
            $country = $table->getById($lang, "countries", (int)$id);
            if($country)
                return [
                    "id"            => (int)$id,
                    "title"         => $country["title"],
                    //"shortcode"     => $country->code,
                    //"dial_codes"    => $country->dial_codes,
                ];
            return false;
        }
        return false;
    }
}