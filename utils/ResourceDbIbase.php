<?php

if(!defined('AccessProtect')) {
    die('Direct access not permitted');
}

require_once(__DIR__.'/ApiGetResourcesFromDb.php');

class ibaseResDatabase {

    // private $db_name = IBASE_NAME;
    private $db_str = IBASE_STR;
    private $db_user = IBASE_USER;
    private $db_pass = IBASE_PASS;

    private $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

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