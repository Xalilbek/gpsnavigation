<?php
namespace Models;

use Lib\MainDB;

class ForgetLogs extends MainDB
{
    public static function getSource(){
        return "logs_forgot";
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