<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');

require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;

class ApiDbReq extends ApiEndpointAccessCheck{

    protected $endpoint_name = 'db_req';
    protected $resource_type = 'db_req';

    // public $db_req = null;
    // public $obj_list = null;


    // protected $sources_list = ['pg', 'ibase_meteo', 'csdn_spb', 'sts'];
    // protected $sources_resources = [];



    public function grantedAction(){

        $db_req = TU\getData('req');
        $obj_list = TU\cleanData(TU\getData('list'));

        $response = null;

        switch ($db_req){
            case 'get_pg_stations':
            break;
            case 'get_ibase_meteo_stations':
                $response = $this->getIbase($obj_list);
            break;
            case 'get_csdn_spb_stations':
                $response = $this->getCsdn1($obj_list);
            break;
            case 'get_csdn_spb_measures':
                $response = $this->getCsdn2($obj_list);
            break;
            case 'get_csdn_spb_stations':
            break;
            case 'get_sts_stations':
            break;
        }

        // $res1 = $this->getCsdn();
        $res2 = $this->getIbase();

        http_response_code(200);
        // echo $res1;
        echo json_encode($response,JSON_UNESCAPED_UNICODE);
        // echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function getCsdn1($id_list = null){
        $client = new SoapClient(SOAP_STR);

        $result=$client->__soapCall("getStationList",array("user"=>"test",
        "pass"=>"test"));

        return $result;

    }

    public function getCsdn2($id_list = null){
        $client = new SoapClient(SOAP_STR);

        $result=$client->__soapCall("getMeasList",array("user"=>"test",
        "pass"=>"test"));

        return $result;

    }


    public function getIbase($id_list = null){
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

        // if ($id)
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

$res = new ApiDbReq();
// echo "res4";
$res->getRequest();


?>
