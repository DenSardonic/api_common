<?php

namespace Utils;
require_once(__DIR__.'/SystemDb.php');


if(!defined('AccessProtect')) {
    die('Direct access not permitted');
}

// require "./vendor/autoload.php";
// use \Firebase\JWT\JWT;

class UUID {
    public $prefix;
    public $entropy = true;
    public $isInSecretList = true;
    public $length = 23;
    public $secretList = [];
    public $salt;

    /**
     * @param string $prefix
     * @param bool $entropy
     */
    public function __construct($prefix = '', $secretList = [], $length = 32 ){
        $this->prefix = $prefix;
        $this->length = $length;
        $this->secretList = $secretList;

        $this->createToken();
        if (count($this->secretList) > 0){
            $this->isInSecretList = true;
            $this->checkSecretList();
        }
    }

    public function createToken(){
        $this->uuid = md5(uniqid($this->prefix, $this->entropy));
        if ($this->length < strlen($this->uuid)){
            $this->uuid = substr($this->uuid, 0, $this->length);
        }

    }

    public function checkSecretList(){
        while($this->isInSecretList){
            if (!in_array($this->uuid, $this->secretList)){
                $this->isInSecretList = false;
            } else {
                $this->createToken();
            }
        }
    }

    public function __toString(){
        return $this->uuid;
    }
} 

class RefreshToken{
    public $refresh_token;
    public function __construct(){
        $this->refresh_token = md5(uniqid(REFRESH_TOKEN_SALT, $this->entropy)).'.'.md5(uniqid());
    }

    public function __toString(){
        return $this->refresh_token;
    }
}

function createTokens($conn, $login){

    // $query = "SELECT auth_token from USERS";
    // $stmt = $conn->prepare($query);
    // $stmt->execute();
    // $secretList = (array) $stmt->fetchAll(PDO::FETCH_COLUMN);
    // $uniqStr = (string) new TU\UUID('', $secretList);
    // $refhresh_time_expired = time() + 60 * 60 * 24 * 30;

//                $refresh_token = $this->genToken('refresh', ["userId"=> $user_str_id]);
    $auth_token = new RefreshToken();
    // $access_token = TU\CommonToken::genToken('access', ["userId"=> $user_str_id, "name" => $name, "tt" => "fvSDaw"]);

    $query = "UPDATE USERS SET auth_token=:auth_token where login=:login";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':login', $login);
    $stmt->bindParam(':auth_token', $auth_token);
    if ($stmt->execute()){
        return ['auth_token' => $auth_token ];
    } else {
        http_response_code(500);
        echo json_encode(['msg' => "smth went wrong"], JSON_UNESCAPED_UNICODE); 
        exit;
    }
}

class Log{

    public $conn = null;

    public function __construct(){
        $dbService = new \DatabaseService();
        $this->conn = $dbService->getConnection();

    }
}

function getIp(){
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key){
        if (!empty($_SERVER[$key])){
            $tmp = explode(',', $_SERVER[$key]);
            $ip = trim(end($tmp));
            if (filter_var($ip, FILTER_VALIDATE_IP)){
                return $ip;
            }
        }
    }
}

function cleanData($value = "") {
    $value = trim($value);
    $value = stripslashes($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value);
    
    return $value;
}

function getData($key = null){
    // autodetect params transferring method
    // GET > POST > JSON
    $method = null;
    $val = null;
    if (count($_GET) > 0 ){
        $method = $_GET;
    } elseif (count($_POST) > 0 ) {
        $method = $_POST;
    } else {
        $temp = json_decode(file_get_contents('php://input'), true);
        if (count($temp) > 0)
            $method = $temp;
    }
    $val = is_null($method) ? null : (is_null($key) ? $method : (isset($method[$key]) ? $method[$key] : null));
    return $val;
}

function transformIntoHeaderMap(array $headers)
{
    $headersWithValues = array_filter($headers, function ($header) { return strpos($header, ':') !== false; });

    $headerMap = [];
    foreach ($headersWithValues as $header) {
            list($key, $value) = explode(':', $header);
            $headerMap[$key] = trim($value);
    }

    return $headerMap;
}

function isGzipHeaderSet(array $headerMap)
{
    return isset($headerMap['Content-Encoding']) && 
        $headerMap['Content-Encoding'] == 'gzip';
}

function getCeils($val, $unit){
    if (is_numeric($val)){
        $val = round($val, 2);
        switch ($unit){
            case 'k':
            case 'K':
            case 'к':
            case 'К':
                return(round($val-273.15,2));
            case 'c':
            case 'C':
                default:
                return(round($val,2));
        }
    } else {
        return null;
    }
}

function getMm($val, $unit){
    if (is_numeric($val)){
        switch ($unit){
            case 'm':
            case 'м':
                return(round($val*1000,2));        
            case 'cm':
            case 'см':
                return(round($val*10,2));
            case 'mm':
            case 'мм':
            default:
                return(round($val,2));
        }
    } else {
        return null;
    }
}

function cmp_moments($a, $b){
    return ($a->moment - $b->moment);
}

// linear
function valLinearInterpolation($x1, $y1, $v1, $x2, $y2, $v2, $x3, $y3, $v3, $x, $y){
    $v = null;
    /*
    | x - x1, x2 - x1 , x3 - x1 |
    | y - y1, y2 - y1 , y3 - y1 | = 0
    | v - v1, v2 - v1 , v3 - v1 |

    (x - x1) * | y2 - y1 , y3 - y1 | - (y - y1) * | x2 - x1 , x3 - x1 | + (v - v1) * | x2 - x1 , x3 - x1 | = 0
               | v2 - v1 , v3 - v1 |              | v2 - v1 , v3 - v1 |              | y2 - y1 , y3 - y1 |

    (x - x1) * ((y2 - y1)*(v3 - v1) - (y3 - y1)*(v2 - v1)) 
    - (y - y1) * ((x2 - x1)*(v3 - v1) - (x3 - x1)*(v2 - v1)) 
    + (v - v1) * ((x2 - x1)*(y3 - y1) - (x3 - x1)*(y2 - y1)) = 0

    

     */
    $v = $v1  + (($y - $y1) * (($x2 - $x1)*($v3 - $v1) - ($x3 - $x1)*($v2 - $v1)) - ($x - $x1) * (($y2 - $y1)*($v3 - $v1) - ($y3 - $y1)*($v2 - $v1))) / (($x2 - $x1)*($y3 - $y1) - ($x3 - $x1)*($y2 - $y1)) ;
    return $v;
}

?>