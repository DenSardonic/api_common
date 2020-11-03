<?php
define('AccessProtect', true);
require_once(__DIR__.'/../config/config.php');

$data = json_encode(getCsdn(),JSON_UNESCAPED_UNICODE);
file_put_contents('csdn_res2.json', $data);
// file_put_contents('csdn_meas2.json', getCsdn('getMeasList'));

function getCsdn($request = "getStationList",  $id_list = null){
    $client = new SoapClient(SOAP_STR);

    $result=$client->__soapCall($request,array("user"=>"test",
    "pass"=>"test"));

    return $result;

}


?>