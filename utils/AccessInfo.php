<?php 

require_once(__DIR__.'/ApiEndpointAccessCheck.php');

use Utils as TU;

// allowed endpoints and resources. For currenr user
class ApiAccessInfo extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'access_info';

    protected $endpoints_list = [];
    protected $resoursces_list = [];
    protected $data_list = [];

    public function grantedAction(){

        $this->message .= '';

        $this->getEndpointsAccessInfo();
        $this->getResourcesAccessInfo();

        $this->logActions('check access info');

        http_response_code(200);
        // echo $res1;
        echo json_encode($this->data_list,JSON_UNESCAPED_UNICODE);
        // echo json_encode($this->endpoints_list,JSON_UNESCAPED_UNICODE);
        exit;
    }


    public function getEndpointsAccessInfo(){
        $data = null;

        if ($this->dontCheckEndpointAccess){
            // show all endpoints
            $query = "SELECT ae.id as access_id, ae.datetime_from as dt_from, ae.datetime_to as dt_to, ae.dont_check_res_accsess as dont_check_res_accsess, 
                e.id as endpoint_id, e.name as endpoint_name, e.path as endpoint_path, e.description as endpoint_description,
                e.has_resources as has_resources, e.request_format as endpoint_request_format, e.response_format as endpoint_response_format

                FROM  ACCESS_ENDPOINT as ae
                left join ENDPOINTS as e on e.id = ae.endpoint_id";
            $stmt = $this->conn->prepare($query);

        } else {
            $query = "SELECT ae.id as access_id, ae.datetime_from as dt_from, ae.datetime_to as dt_to, ae.dont_check_res_accsess as dont_check_res_accsess, 
                e.id as endpoint_id, e.name as endpoint_name, e.path as endpoint_path, e.description as endpoint_description,
                e.has_resources as has_resources, e.request_format as endpoint_request_format, e.response_format as endpoint_response_format

                FROM  ACCESS_ENDPOINT as ae
                left join ENDPOINTS as e on e.id = ae.endpoint_id 
                where ae.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->userId);
        }

        $stmt->execute();

        $this->endpoints_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getResourcesAccessInfo(){
        // $data = null;
        $this->data_list = [];
        foreach ($this->endpoints_list as $val){
            $resources = [];
            $has_resources = true;
            if ($val['has_resources']){
                // then show resources
                if ($val['dont_check_res_accsess']){
                    // show all resources with type, matching to endpoint
                    $query = "SELECT DISTINCT r.id as resource_id, r.local_id as resource_local_id, r.name as resource_name,
                        r.synindex as resource_index, r.lat as resource_lat, r.lng as resource_lng,
                        rt.descr as resource_type_description
                        FROM  RESOURCES as r
                        left join RESOURCES_TYPES as rt on rt.id = r.type
                        where r.type in ( 
                            select ert.resource_type
                            from ENDPOINTS_RESOURCES_TYPES as ert
                            where ert.endpoint_id = :endpoint_id
                        ) OR r.id in (
                            SELECT exr.resource_id
                            FROM EXTENDED_RESOURCES_TYPE as exr
                            WHERE exr.resource_type_id in (
                                select ert2.resource_type
                                from ENDPOINTS_RESOURCES_TYPES as ert2
                                where ert2.endpoint_id = :endpoint_id2
                            )
                        ) ";  


                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':endpoint_id', $val['endpoint_id']);
                        $stmt->bindParam(':endpoint_id2', $val['endpoint_id']);
                        
                        $stmt->execute();

                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                            $temp = [];
                            $temp['id'] = $row['resource_local_id'];
                            $temp['name'] = $row['resource_name'];
                            $temp['synindex'] = $row['resource_index'];
                            $temp['type'] = $row['resource_type_description'];
                            $temp['lat'] = $row['resource_lat'];
                            $temp['lng'] = $row['resource_lng'];
                            $temp['allow_from'] = null;
                            $temp['allow_to'] = null;

                            $resources []= $temp;
                        }
                } else {
                    $query = "SELECT ar.datetime_from as dt_from, ar.datetime_to as dt_to,
                        r.id as resource_id, r.local_id as resource_local_id, r.name as resource_name,
                        r.synindex as resource_index, r.lat as resource_lat, r.lng as resource_lng,
                        rt.descr as resource_type_description
                        FROM  ACCESS_RESOURCE as ar
                        left join RESOURCES as r on r.id = ar.resource_id 
                        left join RESOURCES_TYPES as rt on rt.id = r.type
                        where ar.user_id = :user_id and ar.endpoint_id = :endpoint_id";  
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':user_id', $this->userId);
                        $stmt->bindParam(':endpoint_id', $val['endpoint_id']);
                        
                        $stmt->execute();

                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                            $temp = [];
                            $temp['id'] = $row['resource_local_id'];
                            $temp['name'] = $row['resource_name'];
                            $temp['synindex'] = $row['resource_index'];
                            $temp['type'] = $row['resource_type_description'];
                            $temp['lat'] = $row['resource_lat'];
                            $temp['lng'] = $row['resource_lng'];
                            $temp['allow_from'] = $row['dt_from'];
                            $temp['allow_to'] = $row['dt_to'];

                            $resources []= $temp;
                        }
                }
            } else {
                $resources = [];
                $has_resources = false;
            }

            $this->data_list []= [
                'endpoint_path' => $val['endpoint_path'],
                'endpoint_date_from' => $val['dt_from'], 
                'endpoint_date_to' => $val['dt_to'], 
                'endpoint_description' => $val['endpoint_description'], 
                'endpoint_request_format' => $val['endpoint_request_format'], 
                'endpoint_response_format' => $val['endpoint_response_format'], 
                'has_resources' => $has_resources,
                'resources' => $resources
            ];
        }

        // return $data;
    }


}

?>