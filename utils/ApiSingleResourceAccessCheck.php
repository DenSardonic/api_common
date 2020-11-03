<?php 

require_once(__DIR__.'/ApiEndpointAccessCheck.php');

use Utils as TU;

class ApiSingleResourceAccessCheck extends ApiEndpointAccessCheck{
    // 
    // protected $endpoint_name = 'register';

    public $local_resource = null;

    public $resource_id = null;
    public $resource_local_id = null;
    public $requested_local_id = null;
    public $requested_from = null;
    public $requested_to = null;


    public function grantedAction(){

        $isAuth = false;
        // $this->message .= ' Resource access denied or contract period expired.';
        $this->message .= '';

        $this->requested_local_id = TU\getData('id');
        $this->requested_from = TU\getData('from');
        $this->requested_to = TU\getData('to');
        if (!$this->requested_local_id || !$this->requested_from || !$this->requested_to){
            $this->message .= " Wrong paramters";
            $this->logActions('RESOURCE access DENIED', "Wrong paramters");

            http_response_code(400);
            echo json_encode(array(
                "message" => $this->message,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        } 

        
        $this->requested_from = TU\cleanData($this->requested_from);
        $this->requested_to = TU\cleanData($this->requested_to);
        $this->requested_local_id = TU\cleanData($this->requested_local_id);

        $this->requested_from = strtotime($this->requested_from);
        $this->requested_to   = strtotime($this->requested_to);

        if ($this->hasResources){

            if ($this->dontCheckResourceAccess){
                $isAuth = $this->checkResourceType();
            } else {
                $isAuth = $this->checkResourceAccess();
            }
        } else {
            $isAuth = false;
        }
            
            
        // }

        if ($isAuth){
            $this->getResourceInfo();
            $this->logActions('RESOURCE access GRANTED',"$this->requested_local_id from: ".date('Y-m-d H:i:s', $this->requested_from)
                                ." to: ". date('Y-m-d H:i:s', $this->requested_to));
            $this->getRequestedResources();
        } else {

            $this->message .= " Access to requested resources is denied.";
            $this->logActions('RESOURCE access DENIED', 
                                "$this->requested_local_id from: ".date('Y-m-d H:i:s', $this->requested_from)
                                ." to: ". date('Y-m-d H:i:s', $this->requested_to));

            http_response_code(403);
            echo json_encode(array(
                "message" => $this->message,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function getResourceInfo(){
        $query = "SELECT r.id as resource_id, r.name as resource_name, r.synindex as synindex,
            r.source_db_ref as source_db_ref, r.source_db_id as source_db_id,
            r.lat as lat, r.lng as lng,
            s.name as source_name, s.id as source_id
            FROM  RESOURCES as r 
            left join SOURCES as s on s.id = r.source
            where r.local_id = :local_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':local_id', $this->requested_local_id);

        $stmt->execute();
        $this->local_resource = $stmt->fetch();
        $this->resource_id = $this->local_resource['resource_id'];
        $this->resource_local_id = $this->requested_local_id;

    }

    public function checkResourceType(){
        $isAuth = false;

        // $query = "SELECT ert.id as id
        //     FROM  endpoints_resources_types as ert
        //     left join ENDPOINTS as e on e.id = ert.endpoint_id 
        //     left join RESOURCES as r on r.type = ert.resource_type
        //     where e.name = :e_name and r.local_id = :local_id";

        // even if there are extended types
        $query = "SELECT ert.id as id
            FROM  endpoints_resources_types as ert
            where ert.endpoint_id = :ep_id and 
            ert.resource_type in (
                select r.type
                from resources as r 
                where r.local_id = :local_id1
                UNION select ext.resource_type_id
                from extended_resources_type as ext
                left join resources as r2 on r2.id = ext.resource_id
                where r2.local_id = :local_id2
            )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':ep_id', $this->endpoint_id);
        $stmt->bindParam(':local_id1', $this->requested_local_id);
        $stmt->bindParam(':local_id2', $this->requested_local_id);

        $stmt->execute();

        $row = $stmt->fetch();

        $isAuth = $row['id'] ? true : false;

        return $isAuth;
    }

    public function checkResourceAccess(){
        $isAuth = false;
    
        // if there is no from and to params, getData returns NULL and timestamp sets to zero value

        // $req_date_from_unix = strtotime($this->requested_from);
        // $req_date_to_unix   = strtotime($this->requested_to);


        $query = "SELECT ar.id as access_id, ar.datetime_from as dt_from, ar.datetime_to as dt_to,
            FROM  ACCESS_RESOURCE as ar
            left join ENDPOINTS as e on e.id = ar.endpoint_id 
            where ar.user_id = :user_id and e.name = :e_name and r.local_id = :local_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':e_name', $this->endpoint_name);
        $stmt->bindParam(':user_id', $this->userId);
        $stmt->bindParam(':local_id', $this->requested_local_id);

        $stmt->execute();

        $row = $stmt->fetch();

        // dontCheckResourceAccess defines in endpoint option
        // if ($this->dontCheckResourceAccess){
        //     $row['dt_from'] = $req_date_from_unix;
        //     $row['dt_to'] = $req_date_to_unix;
        //     $isAuth = true;
        // } else {
            
        $this->requested_from = $row['dt_from'] == null ? $this->requested_from : strtotime($row['dt_from']);
        $this->requested_to = $row['dt_to'] == null ? $this->requested_to : strtotime($row['dt_to']);

        $isAuth = $row['access_id'] && ($this->requested_from <= $this->requested_to) ? true : false;

        // }

        return $isAuth;

    }

    public function getRequestedResources(){

    }

}

?>