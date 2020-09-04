<?php
namespace Lib;

class MyMongo
{
    public $connection = false;

    public $db = MONGO_DB;

    public $collection = false;

    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function init()
    {
        if(!$this->connection)
        //    $this->connection = new \MongoDB\Driver\Manager();
            $this->connection = new \MongoDB\Driver\Manager('mongodb://localhost:27017/'.$this->db);
    }

    public function insert($collection, $data)
    {
        $this->init();
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $id = $insRec->insert($data);
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return $id;
        }
        else
        {
            return false;
        }
    }

    public function save($collection, $data)
    {
        $this->insert($collection, $data);
    }

    public function rawFind($collection, $filter, $options = [])
    {
        /*
         * $filter = [
         *      'id'    => 1
         * ]
         * $options - [
         *      'sort' => ['_id' => -1],
         * ]
         */
        $this->init();
        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$collection, $query);
        return $rows->toArray();
    }

    public function count($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        $this->init();

        $Command = new \MongoDB\Driver\Command(["count" => $this->collection, "query" => $filter]);
        $Result = $this->connection->executeCommand($this->db, $Command);
        return $Result->toArray()[0]->n;
    }

    public function find($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        if(isset($array["limit"]))
            $options["limit"]    = @$array["limit"];
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        $this->init();

        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);

        return $rows->toArray();
    }

    public function findById($id)
    {
        $filter["_id"] = $this->objectId($id);
        $this->init();
        $query  = new \MongoDB\Driver\Query($filter, []);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);
        foreach($rows as $row)
            return $row;
        return false;
    }

    public function rawFindFirst($collection, $filter, $options = [])
    {
        $options["limit"] = 1;
        $this->init();
        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$collection, $query);
        if(count($rows) > 0)
            foreach($rows as $row)
                return $row;
        return false;
    }

    public function findFirst($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        $options["limit"]   = 1;
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        $this->init();
        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);
        foreach($rows as $row)
                return $row;
        return false;
    }

    public function update($collection, $filter, $data)
    {
        /*
         * ['_id'=>new \MongoDB\BSON\ObjectID($id)],
         *
         * $filter = [
         *      'id'    => 1
         * ]
         * $options - [
         *      'sort' => ['_id' => -1],
         * ]
         */
        $this->init();
        $options = ['multi' => false, 'upsert' => false];
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $insRec->update(
            $filter,
            ['$set' => $data],
            $options
        );
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function increment($collection, $filter, $data)
    {
        $this->init();
        $options = ['multi' => false, 'upsert' => false];
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $insRec->update(
            $filter,
            ['$inc' => $data],
            $options
        );
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function delete($collection, $filter)
    {
        $this->init();
        $bulk   = new \MongoDB\Driver\BulkWrite;
        $bulk->delete($filter, ['limit' => 0]);
        $result = $this->connection->executeBulkWrite($this->db.'.'.$collection, $bulk);
        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getDate($time=false)
    {
        if(!$time)
            $time=time();
        $time *= 1000;
        $datetime = new \MongoDB\BSON\UTCDateTime($time);
        return $datetime;
    }

    public function dateTime($date)
    {
        if(@$date)
            return strtotime(@$date->toDateTime()->format("Y-m-d H:i:s"));
        return 0;
    }

    public function dateFormat($date, $format = "Y-m-d H:i:s")
    {
        if(@$date)
            return @$date->toDateTime()->format($format);
        return 0;
    }

    public function toSeconds($date)
    {
        if(@$date)
            return round(@$date->toDateTime()->format("U.u"), 0);
        return 0;
    }

    public function objectId($id)
    {
        return new \MongoDB\BSON\ObjectID($id);
    }
}