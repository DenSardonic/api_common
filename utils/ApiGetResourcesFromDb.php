<?php

if(!defined('AccessProtect')) {
    die('Direct access not permitted');
}

require_once(__DIR__.'/../config/config.php');

class ApiGetResourcesFromDb {

    private $connection;

    public function getConnection(){
        $this->connection = null;

        try {

            $this->connection = new PDO($this->db_str, $this->db_user, $this->db_pass, $this->opt);
        
        } catch (PDOException $e) {
            header('http/1.1 500 internal server error'); 
            echo json_encode(['errcode' => '1', 'msg' => $e->getMessage() ],JSON_UNESCAPED_UNICODE);
            exit(); 
        }
        
        return $this->connection;

    }

}

?>