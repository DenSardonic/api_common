<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;

class ApiLastRadio extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'radio_last';
    // protected $endpoint_name = 'meteo_last';

    public $resource_types = array();
    public $resources_info = array();
    public $resources_id = array();

    public $main_data = array();


    public function grantedAction(){
        $response_data = [];
        $this->getUserResources();

        $this->mainAction();
        
        foreach($this->main_data as $val){
            $temp = [];
            $temp['resource_name'] = $val['resource_name'];
            $temp['synindex'] = 'n/a';
            $temp['local_id'] = $val['resource_local_id'];
            $temp['resource_lat'] = $val['resource_lat'];
            $temp['resource_lng'] = $val['resource_lng'];
            $temp['datetime'] = $val['datetime'];
            $temp['timezone'] = '+3';
            $temp['radiation'] = $val['radiation'];
            $temp['unit'] = 'UR/h';
            $response_data []= $temp;
        }

        $this->logActions('GET RADIATIONS');

        http_response_code(200);
        echo json_encode($response_data, JSON_UNESCAPED_UNICODE );
        // echo json_encode($this->resources_id, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function getUserResources(){
        // check binded types if there is no limitation for user or 
        // there is a whole type binded for user
        $query = "SELECT ert.resource_type as resource_type
                from endpoints_resources_types as ert
                where ert.endpoint_id = :endpoint_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':endpoint_id', $this->endpoint_id);

        $stmt->execute();
        while($row = $stmt->fetch()){
            $this->resource_types []= $row['resource_type'];
        }

        // if resources instanses are binded
        $query = "SELECT ar.resource_id as resource_id, ar.resource_local_id as resource_local_id,
            ar.datetime_from as datetime_from, ar.datetime_to as datetime_to,
            r.name as resource_name, s.name as source_name, r.source_db_id as source_db_id,
            r.lat as resource_lat, r.lng as resource_lng
            from access_resource as ar
            left join resources as r on r.id = ar.resource_id
            left join resources_types as rt on rt.id = r.type
            left join sources as s on s.id = r.source
            where ar.endpoint_id = :endpoint_id and ar.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':endpoint_id', $this->endpoint_id);
        $stmt->bindParam(':user_id', $this->userId);

        // echo " $this->endpoint_id $this->userId";
        // exit;

        $stmt->execute();
        while($row = $stmt->fetch()){
            $temp = [];
            $temp['resource_id'] = $row['resource_id'];
            $temp['resource_local_id'] = $row['resource_local_id'];
            $temp['datetime_from'] = $row['datetime_from'];
            $temp['datetime_to'] = $row['datetime_to'];
            $temp['source_db_id'] = $row['source_db_id'];
            $temp['resource_name'] = $row['resource_name'];
            $temp['source_name'] = $row['source_name'];
            $temp['resource_lat'] = $row['resource_lat'];
            $temp['resource_lng'] = $row['resource_lng'];
            // $this->resources_info[$row['source_db_id']] = $temp;
            $this->resources_id []= $row['source_db_id'];
            $this->main_data[$row['resource_id']] = $temp;
        }
    }

    public function mainAction(){
        $temp_data = array();
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

        $row = array();
        foreach ($this->main_data as $key => $val){
            $row['datetime'] = null;
            $query = "select f.sobj, cast(f.smax_day || '.' || f.smax_month || '.' || f.smax_year || ' ' || f.smax_hour || ':' || f.smax_minute as timestamp) as datetime
                from stable_fill f  
                where f.sobj = :sobj";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':sobj', $val['source_db_id']);

            $stmt->execute();
            
            $row = $stmt->fetch();
            $this->main_data[$key]['datetime'] = $row['DATETIME'];
            // $temp_data = $stmt->fetchAll();
        }
        // return;
        foreach ($this->main_data as $val){
            if (!is_null($val['datetime'])){
                $query = "select r.radiation_level as r_level
                    from rhob r
                    where r.sobj = :sobj and r.sdate_time = :sdate_time";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':sobj', $val['source_db_id']);
                $stmt->bindParam(':sdate_time', $val['datetime']);
                $stmt->execute();

                $row = $stmt->fetch();
            } else {
                $row['R_LEVEL'] = null;
            }

            // $this->main_data = $stmt->fetchAll();   
            $this->main_data[$val['resource_id']]['radiation'] = $row['R_LEVEL'];
        }
    }

}

$endpoint_class = new ApiLastRadio();
$endpoint_class->getRequest();


?>