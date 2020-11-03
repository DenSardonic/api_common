<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');




use Utils as TU;

class ApiGetId extends ApiEndpointAccessCheck{
    // 
    protected $endpoint_name = 'get_id';

    public $local_resource = null;

    public $resource_local_id = null;
    public $requested_id = null;
    // public $requested_from = null;
    // public $requested_to = null;


    public function grantedAction(){

        $isAuth = false;
        $this->message .= '';

        $this->requested_id = TU\getData('id');
        // echo $this->requested_id;
        // exit;

        if (!$this->requested_id){
            $this->message .= " Wrong paramters";
            $this->logActions('RESOURCE access DENIED', "Wrong paramters");

            http_response_code(400);
            echo json_encode(array(
                "message" => $this->message,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        } 

        $this->requested_id = TU\cleanData($this->requested_id);


        if ($this->dontCheckResourceAccess){
            $isAuth = true;
            $this->getId();
        } else {
            $isAuth = false;
        }


        if ($isAuth){
            $this->logActions('RESOURCE access GRANTED',$this->requested_id);
            $this->getRequestedResources();
        } else {

            $this->message .= " Access to requested resources is denied.";
            $this->logActions('RESOURCE access DENIED', $this->requested_id);

            http_response_code(403);
            echo json_encode(array(
                "message" => $this->message,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function getId(){
        // $isAuth = false;

        // even if there are extended types
        $query = "SELECT r.local_id, r.type as resource_main_type, rt.descr as resource_main_type_description,
                s.id as source_id, s.name as source_database, s.descr as source_description
                from resources as r 
                left join sources as s on s.id = r.source
                left join resources_types as rt on rt.id = r.type
                where r.synindex = :synindex
            ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':synindex', $this->requested_id);

        $stmt->execute();

        $this->local_resource = $stmt->fetchAll();

        // return $isAuth;
    }


    public function getRequestedResources(){
        http_response_code(200);
        echo json_encode($this->local_resource, JSON_UNESCAPED_UNICODE );
        // echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

}

$res = new ApiGetId();
$res->getRequest();


?>