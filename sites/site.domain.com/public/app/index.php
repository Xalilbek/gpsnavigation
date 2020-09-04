<?php
if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'iphone') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'ipad') !== false){
    $url = "https://itunes.apple.com/ua/app/utigps/id1449786042?mt=8";
}elseif(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
    $url = "market://details?id=com.utigps";
}else{
    $url = "http://utigps.com";
}

header("Location: ".$url);
exit;
