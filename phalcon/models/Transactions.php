<?php
namespace Models;

use Lib\MainDB;

class Transactions extends MainDB
{
    public static function getSource(){
        return "transactions";
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

    public static function getTypeList($lang)
    {
        return [
            "withdraw" => [
                "title"     => mb_strtolower($lang->get("Withdraw")),
                "bg_color"  => "#dc2c1f",
                "color"  => "#fbfbfb",
            ],
            "fund" => [
                "title"     => mb_strtolower($lang->get("Fund")),
                "bg_color"  => "#17a2b8",
                "color"  => "#fbfbfb",
            ],
            "charge" => [
                "title"     => mb_strtolower($lang->get("Charge")),
                "bg_color"  => "#ff9800",
                "color"     => "#fbfbfb",
            ],
        ];
    }

    public static function getSourceList($lang)
    {
        return [
            "azcard" => [
                "title" => "Azcard",
            ],
            "panel" => [
                "title" => mb_strtolower($lang->get("Panel")),
            ],
            "cash" => [
                "title" => mb_strtolower($lang->get("Cash")),
            ],
            "system" => [
                "title" => mb_strtolower($lang->get("System")),
            ],
        ];
    }
}