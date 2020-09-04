<?php
namespace Models;

use Lib\MainDB;

class LogsRawTracking extends MainDB
{
    public static function getSource(){
        return "logs_raw_tracking";
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