<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once(__DIR__.'/../utils/AuthAccess.php');

require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;

class ApiTest extends AuthAccess{

    protected $endpoint_name = 'test';
    protected $resource_type = 'test';

    // protected $sources_list = ['pg', 'ibase_meteo', 'csdn_spb', 'sts'];
    // protected $sources_resources = [];



    public function grantedAction(){

        // $res1 = $this->getCsdn();
        $res2 = $this->getIbase();

        http_response_code(200);
        // echo $res1;
        echo json_encode($res2,JSON_UNESCAPED_UNICODE);
        // echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function getCsdn(){
        $client = new SoapClient(SOAP_STR);

        $result=$client->__soapCall("getStationList",array("user"=>"test",
        "pass"=>"test"));


        return $result;

    }


    public function getIbase(){
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

        $query =  "select * from sobjects";
        $stmt = $conn->prepare($query);

        $stmt->execute();

        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }


    public function getSts($ids_list){
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "sts",
            "status" => 0 
        ];
        $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;

    }


    public function getPg($ids_list){
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "pg",
            "status" => 0 
        ];
        $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;
    }

}

$res = new ApiTest();
// echo "res4";
$res->getRequest();


?>
