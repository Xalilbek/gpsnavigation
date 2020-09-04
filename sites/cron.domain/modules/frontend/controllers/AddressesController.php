<?php
namespace Controllers;

use Models\Address;
use Models\History;
use Models\LogsRawTracking;
use Models\LogsTracking;
use Models\LogsUnknownTracking;
use Models\Objects;
use Models\Statistics;

class AddressesController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {

        $phpStart = microtime(true);

        for($i=0;$i<1;$i++)
        {
            $objects = Objects::find([
                [],
                "sort"  => [
                    "address_checked_at" => 1
                ],
                "limit" => 100,
            ]);

            foreach($objects as $value)
            {
                echo "ID: ".$value->id." ".@$value->address->created_at."<br/>";


                $update = [
                    "address_checked_at"           => Objects::getDate(),
                ];

                list($lon, $lat) = Objects::getLonLatFromGeometry($value->geometry);
                $location = Address::getByCoords($lat, $lon);
                if($location){
                    echo "Address: ".$location["name"].", ";

                    $update["address"] = $location;
                }else{
                    echo "Address: Not found, ";
                }

                Objects::update(
                    [
                        "_id" => $value->_id,
                    ],
                    $update
                );


                if(microtime(true) - $phpStart > 50)
                    exit;
            }

            //sleep(2);
            if(microtime(true) - $phpStart > 50)
                exit;
        }

        exit;
    }


    public function disAction(){
        $historyDistance = LogsTracking::sum("duration", ["history_id" => "5c2abd7187d2db74e3683fa0"]);
        var_dump($historyDistance);
        exit;
    }
}