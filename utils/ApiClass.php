<?php 
define('AccessProtect', true);
require_once(__DIR__.'/SystemDb.php');
require_once(__DIR__.'/utils.php');
use Utils as TU;
// require "./vendor/autoload.php";
// use \Firebase\JWT\JWT;

class Api{

    public $headers = [];
    public $conn = null;
    // public $isAuth = false;
    // public $authHeader = '';
    // public $access_token = null;
    public $data = null;
    // public $content_type;

    public $debug_mode = false;
    public $debug = "";

    public $req_method = null;

    public function __construct(){
        $this->headers = getallheaders();
        $dbService = new DatabaseService();
        $this->conn = $dbService->getConnection();

        $this->debug_mode = TU\getData('debug') ? true : false;
        // ! get Data only in endpoints or for concrete purposes
        // $this->data = $this->getData();
    }
    
    public function getRequest(){
        $this->req_method = $_SERVER['REQUEST_METHOD'];

        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        // if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        if ($this->req_method == 'OPTIONS'){
            http_response_code(200);
            $this->logActions('OPTIONS method');
            exit;
        } 
        // if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
        if ($this->req_method == 'POST' || $this->req_method == 'GET') {
        
            header("Access-Control-Allow-Headers: Content-Type, origin, Access-Control-Allow-Headers, Authorization, X-Requested-With");
            header("Access-Control-Max-Age: 3600");
            // header("Access-Control-Allow-Credentials: true");
            header("Content-Type: application/json; charset=utf-8");

            $this->actions();

        } else {
            http_response_code(405);
            $this->logActions('wrong method');
            exit;        
        }

    }

    // public function getData(){

    //     $this->req_method = $_SERVER['REQUEST_METHOD'];
    //     $temp = $_GET;
    //     if (count($temp) > 0 ) return $temp;
        
    //     $temp = $_POST;
    //     if (count($temp) > 0 ) return $temp;

    //     $temp = json_decode(file_get_contents('php://input'), true);
    //     if (count($temp) > 0 ) return $temp;

    //     return null;
    // }

    public function actions(){

    }

    public function logActions($status = ""){
    }

}






?>