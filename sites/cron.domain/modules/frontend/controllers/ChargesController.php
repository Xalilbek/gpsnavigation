<?php
namespace Controllers;

use Models\History;
use Models\LogsRawTracking;
use Models\LogsTracking;
use Models\LogsUnknownTracking;
use Models\Objects;
use Models\Statistics;
use Models\Transactions;
use Models\Users;

class ChargesController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        echo "Starts<br/>";
        $phpStart = microtime(true);

        Objects::update(["last_charge_attempted_at" => null], ["last_charge_attempted_at" => Objects::getDate(2)]);
        Objects::update(["next_charge_date" => null], ["next_charge_date" => Objects::getDate(2)]);
        Objects::update(["status" => null], ["status" => 1]);

        for($i=0;$i<10;$i++)
        {
            $binds = [
                "last_charge_attempted_at" => [
                    '$lt' => Objects::getDate(time() - 300)
                ],
                "next_charge_date" => [
                    '$lt' => Objects::getDate()
                ],
                "owner_id"  => [
                    '$gt' => 0
                ],
                "status" => ['$gt' => 0],
                "is_deleted" => ['$ne' => 1],
            ];

            $objects = Objects::find([
                $binds,
                "sort"  => [
                    "last_charge_attempted_at" => 1
                ],
                "limit" => 30,
            ]);

            if(count($objects) == 0)
                echo "No object found<br/>";

            foreach($objects as $value)
            {
                echo "ID: ".$value->id." ".Objects::dateFormat($value->next_charge_date, "Y-m-d H:i:s")."<br/>";

                $nextChargeDate = time()+30*24*3600;

                $update = [
                    "last_charge_attempted_at"    => Objects::getDate(),
                    "status"                      => 2,
                ];

                $user = Users::getById($value->owner_id);
                if($user)
                {
                    $amount = 3;
                    if($user->balance >= $amount)
                    {
                        Users::increment(
                            [
                                "id"	=> (int)$user->id
                            ],
                            [
                                "balance"	=> -1 * $amount
                            ]
                        );


                        $P 				= new Transactions();
                        $P->partner_id 	= 1;
                        $P->object_id 	= (int)$value->id;
                        $P->user_id 	= (int)$user->id;
                        $P->amount 		= $amount;
                        $P->source 		= "system";
                        $P->type 		= "charge";
                        $P->logs 		= [
                            "before_charge" => $user->balance,
                        ];
                        $P->created_at 	= Transactions::getDate();
                        $P->save();

                        $update = [
                            "last_charge_attempted_at"  => Objects::getDate(),
                            "charged_at"                => Objects::getDate(),
                            "next_charge_date"          => Objects::getDate($nextChargeDate),
                            "status"                    => 1,
                        ];

                        echo "Charged<br/>";
                    }else{
                        echo "Balance is insufficient<br/>";
                    }
                }else{
                    echo "No user<br/>";
                }


                echo "Next charge date: ".date("Y-m-d H:i:s", $nextChargeDate)."<br/>";

                Objects::update(
                    [
                        "_id" => $value->_id,
                    ],
                    $update
                );

                echo "<hr/>";

                if(microtime(true) - $phpStart > 50)
                    exit;
            }

            sleep(4);
            if(microtime(true) - $phpStart > 50)
                exit;
        }

        exit;
    }
}