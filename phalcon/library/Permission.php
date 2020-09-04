<?php
namespace Lib;

use Multiple\Models\Cache;

class Permission{
    public $data;

    public $lang = "en";

    public $permissions = [
        "users"
    ];

    public function init($templateId, $lang=false)
    {

    }

    public function getEmployeeConstruct($lang)
    {
        return [
            "cases" => [
                "title"         => $lang->get("Cases"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "todo" => [
                "title"         => $lang->get("TodoList"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "notes" => [
                "title"         => $lang->get("Notes"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "calendar" => [
                "title"         => $lang->get("Calendar"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","add"
                ],
            ],
        ];
    }

    public function getEmployeeList($lang)
    {
        $permissions = [];
        foreach($this->getConstruct($lang) as $key => $value)
        {
            $types = [];
            foreach($value["permissions"] as $type)
                $types[$type] = $this->getType($type, $lang);

            $permissions[$key] = [
                "title"         => $value["title"],
                "permissions"   => $types,
            ];
        }

        return $permissions;
    }

    public function getConstruct($lang)
    {
        return [
            "citizens" => [
                "title"         => $lang->get("Citizens"),
                "add", "permissions"   => [
                   "all", "view", "add", "edit", "delete",
                ],
            ],
            "cases" => [
                "title"         => $lang->get("Cases"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "settings" => [
                "title"         => $lang->get("Settings"),
                "permissions"   => [
                    "all",  "view", "add", "edit", "delete",
                ],
            ],
            "todo" => [
                "title"         => $lang->get("TodoList"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "notes" => [
                "title"         => $lang->get("Notes"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "calendar" => [
                "title"         => $lang->get("Calendar"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "partners" => [
                "title"         => $lang->get("Partners"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "employees" => [
                "title"         => $lang->get("Employees"),
                "permissions"   => [
                    "all",  "view", "add", "edit", "delete",
                ],
            ],
            "moderators" => [
                "title"         => $lang->get("Moderators"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "jobdatabase" => [
                "title"         => $lang->get("JobList"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "knowledgebase" => [
                "title"         => $lang->get("Knowledgebase"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","add"
                ],
            ],
            "worklog" => [
                "title"         => $lang->get("WorkLog"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "mailbox" => [
                "title"         => $lang->get("Mailbox"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "offers" => [
                "title"         => $lang->get("Offer"),
                "permissions"   => [
                    "all", "view", "add", "delete",
                ],
            ],
            "translations" => [
                "title"         => $lang->get("Translations"),
                "permissions"   => [
                    "all", "view", "edit",
                ],
            ],

        ];
    }

    public function getList($lang)
    {
        $permissions = [];
        foreach($this->getConstruct($lang) as $key => $value)
        {
            $types = [];
            foreach($value["permissions"] as $type)
                $types[$type] = $this->getType($type, $lang);

            $permissions[$key] = [
                "title"         => $value["title"],
                "permissions"   => $types,
            ];
        }

        return $permissions;
    }

    public function getType($type, $lang)
    {
        $permissionTypes = [
            "all"       => $lang->get("All"),
            "add"       => $lang->get("Add"),
            "view"      => $lang->get("View"),
            "edit"      => $lang->get("Edit"),
            "delete"    => $lang->get("Delete"),
        ];
        return $permissionTypes[$type];
    }
}