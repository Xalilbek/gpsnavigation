<?php
namespace Models;

use Lib\MainDB;

class Users extends MainDB
{
    public static function getSource(){
        return "users";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getByPhone($phone)
    {
        return self::findFirst([
            [
                "phone"         => $phone,
                "is_deleted"    => 0
            ]
        ]);
    }

    public static function getByUsername($username)
    {
        return self::findFirst([
            [
                "username"         => strtolower($username),
                //"is_deleted"       => 0
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



    public static function statusList($lang)
    {
        return [
            0 => [
                "label"     => $lang->get("Unverified"),
                "color"     => "#ed0000",
            ],
            1 => [
                "label"     => $lang->get("Active"),
                "color"     => "#1f9810",
            ],
            2 => [
                "label"     => $lang->get("InModeration", "In moderation"),
                "color"     => "#ff7810",
            ],
        ];
    }

}