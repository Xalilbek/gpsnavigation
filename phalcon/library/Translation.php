<?php
namespace Lib;

use Models\Translations;
use Models\Cache;

class Translation
{
    public $data;

    public $lang = "en";

    public $templateId = 1;

    public $cacheSeconds = 10;

    public $langs = ["en","ru","az"];

    public $testLangs = ["en","ru","az"];

    public $langData = [
        "en" => ["name" => "English"],
        "da" => ["name" => "Danish"],
        "ru" => ["name" => "Русский язык"],
        "ua" => ["name" => "Українська мова"],
        "tr" => ["name" => "Türk dili"],
        "az" => ["name" => "Azərbaycan dili"],
        "de" => ["name" => "Deutsch"],
    ];

    public $templates = [
        1   => ["name"  => "web"],
        2   => ["name"  => "partner_api"],
        3   => ["name"  => "admin_api"],
        4   => ["name"  => "admin_frontend"],
        5   => ["name"  => "partner_frontend"],
        6   => ["name"  => "app"],
    ];

    public function init($templateId, $lang=false)
    {
        if($lang)
        {
            $this->lang = $lang;
        }
        elseif(strlen(@$_POST["lang"]) > 1)
        {
            $this->lang = @$_POST["lang"];
        }
        else if(strlen(@$_GET["lang"]) > 1)
        {
            $this->lang = @$_GET["lang"];
            if(@$_COOKIE['lang'] !== $this->lang){
                //  setcookie(")", $this->lang, time()+365*24*3600, "/");
            }
        }
        else if(strlen(@$_COOKIE['lang']) > 1)
        {
            $this->lang = @$_COOKIE['lang'];
        }
        else if(!$lang || !in_array($lang, $this->langs))
        {
            $this->lang = _MAIN_LANG_;
        }
        else
        {
            $this->lang = $lang;
        }
        define("_LANG_", $this->lang);
        $this->setLang($this->lang);


        $this->templateId   = $templateId;
        if(!$this->data)
        {
            $this->getTranslationsBySiteID($this->templateId, $this->lang);
        }
        return true;
    }

    public function setLang($lang)
    {
        return $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function getLangs()
    {

    }

    public function getTranslationsBySiteID($templateId, $lang)
    {
        $lang       = strtolower($lang);
        $data       = $this->getFromCache();
        if(!$data)
        {
            $data   = [];
            $query  = Translations::find([["template_id"   => (int)$templateId, "is_deleted" => ['$ne' => 1]]]);
            if(count($query) > 0)
            {
                foreach($query as $value)
                {
                    $translation = mb_strlen($value->translations->$lang) > 0 ? $value->translations->$lang: $value->translations->en;
                    if(mb_strlen($translation) == 0)
                        foreach($value->translations as $langKey => $langValue)
                        {
                            if(mb_strlen($langValue) > 0){
                                $translation = $langValue;
                                break;
                            }
                        }
                    $data[$value->key] = $translation;
                }
            }
            $this->saveCache($data);
        }

        return $this->data = $data;
    }

    public function get($key, $original=false)
    {
        $translation = trim($key);
        if($this->data !== false)
        {
            if(@$this->data[$key])
            {
                $translation = @$this->data[$key];
            }
            elseif(strlen($key) > 0)
            {
                $this->add($key, $original);
                $translation = ($original) ? $original: $key;
                $this->data[$key] = $translation;
            }
        }
        return $translation;
    }

    public function add($key, $original=false)
    {
        $key = trim($key);
        if(strlen($key) > 0 && !$data = Translations::findFirst([["key" => trim($key), "is_deleted" => ['$ne' => 1]]]))
        {
            $insert = [
                "template_id"   => [$this->templateId],
                "key"           => $key,
                "translations"  => [
                                        "en"    => ($original) ? $original: $key
                                    ],
                "is_deleted"    => 0,
                "created_at"    => Translations::getDate()
            ];

            Translations::insert($insert);

            $this->flushCache();
        }
        else
        {
            if(!in_array($this->templateId, $data->template_id))
            {
                if(is_array($data->template_id))
                {
                    $data->template_id[]     = $this->templateId;
                }
                else
                {
                    $data->template_id     = [$this->templateId, (int)$data->template_id];
                }
                Translations::update(["key" => trim($key)], ["template_id"   => $data->template_id]);
            }
        }
        return true;
    }

    public function getFromCache()
    {
        return Cache::get($this->getCacheKey());
    }

    public function getCacheKey()
    {
        return md5("translations-".$this->lang."-".$this->templateId);
    }

    public function flushCache()
    {
        return Cache::set($this->getCacheKey(), false, time());
    }

    public function saveCache($data)
    {
        return Cache::set($this->getCacheKey(), $data, time() + $this->cacheSeconds);
    }
}