<?php
namespace Models;

use Lib\MainDB;

class Statistics extends MainDB
{
    public static function getSource(){
        return "statistics";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }
}