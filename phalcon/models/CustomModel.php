<?php
namespace Models;

use Lib\MainDB;

class CustomModel extends MainDB
{
    public static $table;
    public static function getSource()
    {
        return self::$table;
    }

    public function __construct($table)
    {
        self::$table = $table;
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
}