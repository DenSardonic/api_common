<?php

if(!defined('AccessProtect')) {
    die('Direct access not permitted');
}

require_once(__DIR__.'/../config/config.php');

class DatabaseService {

    // private $db_host = "localhost";
    // private $db_user = "root";
    // private $db_pass = "";
    // private $db_name = "perform";
    // private $db_port = "3306";

    // private $db_host = DB_HOST;
    private $db_user = DB_USER;
    private $db_pass = DB_PASS;
    // private $db_name = DB_NAME;
    // private $db_port = DB_PORT;
    // private $db_str = DB_STR;

    private $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    private $connection;


    public function getConnection(){
        $this->connection = null;
        try {

            // $this->connection = new PDO("mysql:host=$this->db_host;dbname=$this->db_name;port=$this->db_port", $this->db_user, $this->db_pass, $this->opt);
            $this->connection = new PDO(DB_STR, $this->db_user, $this->db_pass, $this->opt);
            
            // header('HTTP/1.1 200 Ok'); 
            // echo json_encode(['errcode' => '0', 'msg' => 'Hello!' ],JSON_UNESCAPED_UNICODE);
            // exit();
        
        } catch (PDOException $e) {
            header('http/1.1 500 internal server error'); 
            echo json_encode(['errcode' => '1', 'msg' => $e->getMessage() ],JSON_UNESCAPED_UNICODE);
            exit(); 
        }
        
        return $this->connection;

    }

}


?>