<?php 
require_once(__DIR__.'/ApiCheckUserAuth.php');
use Utils as TU;

//check access to endpoint
class ApiEndpointAccessCheck extends ApiCheckUserAuth{
    protected $userId;
    
    // ! F**C variables notation a like both camel and snake. And don't like kebab
    protected $auth_token = null;
    protected $endpoint_name = '';

    // ? i dont decide is it needfull
    protected $endpoint_id = null;
    protected $message = '';

    protected $user_name = null;
    protected $user_login = null;
    protected $dontCheckEndpointAccess = false;
    protected $dontCheckResourceAccess = false;
    protected $hasResources = false;
    protected $request_format = "";
    protected $rsponse_format = "";
    protected $checkOnlyType = false;

    public $resource_id = null;


    public function setEndpointName($endpoint_name){
        $this->endpoint_name = $endpoint_name;
    }

    // public function actions(){

    //     $this->auth_token = (isset($this->headers['Authorization'])) 
    //         ? explode(" ", $this->headers['Authorization'])[1] 
    //         : TU\getData('token');
    //     $this->auth_token = TU\cleanData($this->auth_token);

    //     if ($this->auth_token == null){
    //         http_response_code(403);
    //         echo json_encode(array(
    //             "message" => "Access denied.",
    //         ), JSON_UNESCAPED_UNICODE);

    //         $this->logActions('no any token');
    //         exit;
    //     }

    //     $this->authorize();

    // }

    public function checkEndpointAccess()
    {
        $isAuth = false;
        // $this->message .= ' Endpoint access denied or contract period expired.';
        // $this->message .= ' Endpoint access denied or contract period expired.';
        
        // $this->checkUserAccessLevel();
        $this->getEndpointInfo();

        if ($this->dontCheckEndpointAccess){
            
            $isAuth = true;

        } else {

            if ($this->endpointAccess())
                $isAuth = true;
            else
                $isAuth = false;

        }
        
        

        if ($isAuth){
            // $this->logActions('ENDPOINT access GRANTED');
            
            $this->grantedAction();
        } else {
            $this->logActions('ENDPOINT access DENIED');
            $this->message .= ' Endpoint access denied or contract period expired.';
            
            http_response_code(403);
            echo json_encode(array(
                "message" => $this->message,
            ), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function getEndpointInfo(){
        $query = "SELECT e.id as ep_id, e.has_resources as has_resources,
            e.request_format as request_format, e.response_format as response_format
            FROM  ENDPOINTS as e  
            where e.name = :e_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':e_name', $this->endpoint_name);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->endpoint_id = $row['ep_id'];
        $this->hasResources = $row['has_resources'];
        $this->request_format = $row['request_format'];
        $this->rsponse_format = $row['response_format'];

    }

    public function endpointAccess(){
        $isAuth = false;
        $cur_datetime = time();

        $query = "SELECT ae.id as id, ae.datetime_from as dt_from, ae.datetime_to as dt_to, 
                        ae.dont_check_res_accsess as dont_check_res_accsess,
                        ae.check_only_type as check_only_type
                FROM  ACCESS_ENDPOINT as ae
                where ae.endpoint_id = :e_id and ae.user_id = :user_id";
        // $query = "SELECT id from USERS where auth_token=:auth_token LIMIT 0, 1";
        $stmt = $this->conn->prepare($query);
        // $stmt->bindParam(':auth_token', $this->auth_token);
        $stmt->bindParam(':e_id', $this->endpoint_id);
        $stmt->bindParam(':user_id', $this->userId);

        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            $dt_from = strtotime($row['dt_from']);
            $dt_to = strtotime($row['dt_to']);
            $this->dontCheckResourceAccess = (bool) $row['dont_check_res_accsess'];
            $this->checkOnlyType = $row['check_only_type'];

            if ($dt_from == null){
                $dt_from = -INF;
            }
            if ($dt_to == null){
                $dt_to = INF;
            }
            $isAuth = $cur_datetime >= $dt_from && $cur_datetime <= $dt_to ? true : false;
        }

        return $isAuth;
    }

    // public function checkUserAccessLevel(){
    //     $query = "SELECT u.id as id, u.name as name, u.login as login,  u.dont_check_ep_acc as dont_check_ep_acc, 
    //                 u.dont_check_res_acc as dont_check_res_acc
    //                 from users as u 
    //                 where u.auth_token = :auth_token LIMIT 0, 1";

    //     $stmt = $this->conn->prepare($query);

    //     $stmt->bindParam(':auth_token', $this->auth_token);

    //     $stmt->execute();
    //     $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
    //     $this->user_name = $row['name'];
    //     $this->user_login = $row['login'];
    //     $this->dontCheckEndpointAccess = (bool) $row['dont_check_ep_acc'];
    //     $this->dontCheckResourceAccess = (bool) $row['dont_check_res_acc'];
    //     $this->userId = $row['id'];
    // }

    public function grantedAction(){
    
    }

    // public function logActions($status = "", $params = ""){

    //     $ip = TU\getIp();
    //     // $resource_id = null;
    //     $http_req = null;
    //     $params = $params." ".$_SERVER['QUERY_STRING'];

    //     $query = "INSERT INTO LOG (user_id, user_login, endpoint_id, endpoint_name, resource_id, method, http_req, status, params, ip)
    //                 VALUES (:user_id, :user_login,  :endpoint_id, :endpoint_name, :resource_id, :method, :http_req, :status, :params, :ip)";

    //     $stmt = $this->conn->prepare($query);

    //     $stmt->bindParam(':user_id', $this->userId);
    //     $stmt->bindParam(':user_login', $this->user_login);
    //     $stmt->bindParam(':endpoint_id', $this->endpoint_id);
    //     $stmt->bindParam(':endpoint_name', $this->endpoint_name);
    //     $stmt->bindParam(':resource_id', $this->resource_id);
    //     $stmt->bindParam(':method', $this->req_method);
    //     $stmt->bindParam(':http_req', $http_req);
    //     $stmt->bindParam(':status', $status);
    //     $stmt->bindParam(':params', $params);
    //     $stmt->bindParam(':ip', $ip);

    //     $stmt->execute();
    //     // $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // }

}



if ( basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])){
    // echo  "directlry";
    $check = new ApiEndpointAccessCheck();
    $check->getRequest();
} else {
    //echo  "required";
}

?>