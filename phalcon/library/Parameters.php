<?php
namespace Lib;

use Models\Cache;

class Parameters
{
    public $table = false;

    public function setCollection($param)
    {
        $this->table = new \Models\Parameters();

        switch($param)
        {
            default:
                exit("typeError");
                $this->table->setCollection("parameters_object_types");
                break;
            case 'object_types': $this->table->setCollection("parameters_object_types"); break;
            case 'languages': $this->table->setCollection("parameters_languages"); break;
            case 'cities': $this->table->setCollection("parameters_cities"); break;
            case 'countries': $this->table->setCollection("parameters_countries"); break;
        }

        return $this->table;
    }

    public function getList($lang, $param, $filter =[], $withKey = false)
    {
        $this->setCollection($param);

        $queryFilter    = ["active" => 1, "is_deleted" => ['$ne' => 1]];
        $queryFilter    = array_merge($queryFilter, $filter);
        $query          = $this->table->find([$queryFilter, "sort" => ["id" => 1]]);
        $data           = [];
        foreach($query as $value)
        {
            $filteredData = $this->filterData($lang, $param, $value);

            if($withKey)
            {
                $data[(int)$value->id] = $filteredData;
            }
            else
            {
                $data[] = $filteredData;
            }
        }
        return $data;
    }

    public function getListByIds($lang, $param, $ids=[], $withKey = false)
    {
        $this->setCollection($param);

        $query  = $this->table->find([["id" => ['$in' => $ids], "active" => 1, "is_deleted" => ['$ne' => 1]], "sort" => ["id" => 1]]);
        $data   = [];
        foreach($query as $value)
        {
            $filteredData = $this->filterData($lang, $param, $value);

            if($withKey)
            {
                $data[(int)$value->id] = $filteredData;
            }
            else
            {
                $data[] = $filteredData;
            }
        }
        return $data;
    }

    public function getById($lang, $param, $id=0)
    {
        $this->setCollection($param);

        $value  = $this->table->findFirst([["id" => (int)$id, "active" => 1, "is_deleted" => ['$ne' => 1]], "sort" => ["id" => 1]]);
        $data   = false;
        if($value)
        {
            $data = $this->filterData($lang, $param, $value);
        }
        return $data;
    }

    public function filterData($lang, $param, $value)
    {
        $title = strlen(trim(@$value->titles->{$lang->getLang()})) > 0 ? $value->titles->{$lang->getLang()}: $value->titles->{$value->default_lang};
        if(strlen(trim($title)) < 1)
            foreach(@$value->titles as $vTitle)
                if(strlen(trim($vTitle)) > 0)
                    $title = trim(htmlspecialchars($vTitle));
        $filteredData = [
            "id"        => (int)$value->id,
            "title"     => $title,
        ];
        if(strlen($value->html_code) > 0)
            $filteredData["html_code"] = (string)$value->html_code;
        if(strlen($value->code) > 0)
            $filteredData["code"] = (string)$value->code;
        return $filteredData;
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