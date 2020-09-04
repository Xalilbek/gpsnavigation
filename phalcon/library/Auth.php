<?php
namespace Lib;

use Models\Cities;
use Models\Countries;
use Models\Currencies;
use Models\Tokens;
use Models\Cache;
use Models\Users;

class Auth
{
    public $data;

    public $error = false;

    public $errorCode = 0;

    public $cacheSeconds = 10;

    public $token;

    public function init($request, $lang)
    {
        $data = false;
        if($request->get("token"))
        {
            $token = Tokens::findFirst([
                [
                    "token"     => trim($request->get("token")),
                    "active"    => 1,
                ]
            ]);
            if($token)
            {
                $this->token = $token->token;
                $data = Users::findFirst([
                   [
                       "id"   =>  (int)$token->user_id
                   ]
                ]);
                if(!$data)
                {
                    $this->error        = $lang->get("AuthExpired", "Authentication expired");
                    $this->errorCode    = 1001;
                }
            }
            else
            {
                $this->error        = $lang->get("AuthExpired", "Authentication expired");
                $this->errorCode    = 1001;
            }
        }
        else
        {
            $this->error        = $lang->get("AuthExpired", "Authentication expired");
            $this->errorCode    = 1001;
        }
        if($data)
            $this->setData($data);
        return $data;
    }

    public function createToken($request, $data)
    {
        $token 		= $this->generateToken(md5($data->id."-".$request->get("REMOTE_ADDR")."-".microtime()), md5($data->id."-".$request->get("HTTP_USER_AGENT")));

        $tokenInsert = [
            "user_id"		=> (float)$data->id,
            "token"			=> $token,
            "ip"			=> htmlspecialchars($request->getServer("REMOTE_ADDR")),
            "device"		=> htmlspecialchars($request->getServer("HTTP_USER_AGENT")),
            "active"		=> 1,
            "created_at"	=> MainDB::getDate()
        ];
        Tokens::insert($tokenInsert);

        return $token;
    }

    public function generateToken($namespace, $name)
    {
        $nhex = str_replace(array('-','{','}'), '', $namespace);
        $nstr = '';
        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    public function filterData($data, $lang)
    {
        $filtered = [
            "id"			    => $data->id,
            //"username"		    => (string)$data->username,
            "fullname"		    => (string)$data->fullname,
            "phone"			    => strlen((string)$data->phone) > 0 ? (string)$data->phone: false,
            "email"			    => (string)$data->email,
            "photo"			    => ($data->avatar_id) ?
                [
                    "small" => FILE_URL."/uploads/".(string)$data->_id."/".(string)$data->avatar_id."/small.jpg",
                    "large" => FILE_URL."/uploads/".(string)$data->_id."/".(string)$data->avatar_id."/medium.jpg",
                ]:
                [
                    "small" => FILE_URL."/assets/images/nophoto.png",
                    "large" => FILE_URL."/assets/images/nophoto.png",
                ],
            "country"		    => Countries::filterCountry((int)$data->country, $lang),
            //"birthdate"		    => (string)Countries::dateFormat($data->birthdate, "Y-m-d"),
            //"gender"		    => [
            //    "slug"          => (string)$data->gender == "female" ? "female": "male",
            //    "title"         => (string)$data->gender == "female" ? $lang->get("Female"): $lang->get("Male")
            //],
            "balance"           => [
                "value"     => round((float)$data->balance, 2),
                "text"      => round((float)$data->balance, 2)." AZN",
                //"text"      => round((double)$data->balance, 2)." ".Currencies::filterById((int)$data->currency, $lang)["title"],
            ],
            //"currency"          => Currencies::filterById((int)$data->currency, $lang),
            "email_verified"    => ($data->email_verified) ? true: false,
            "phone_verified"    => ($data->phone_verified) ? true: false,
            "type"              => $data->type,
        ];

        return $filtered;
    }

    public function refreshData()
    {
        $data = Users::getById($this->data->id);
        return $this->data = $data;
    }

    public function setData($data)
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getFromCache()
    {
        return Cache::get($this->getCacheKey());
    }

    public function getCacheKey()
    {
        return md5("auth-d");
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