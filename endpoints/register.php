<?php 

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');

use Utils as TU;

class registerUser extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'register';

    public function grantedAction(){
        $login = TU\cleanData(TU\getData('login'));
        $pass = TU\cleanData(TU\getData('pass'));
        $name = TU\cleanData(TU\getData('name'));
        
        $query = "SELECT id from USERS where login=:login";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $login);

        if ($stmt->execute()){
            $num = $stmt->rowCount();
            if ($num > 0){
                http_response_code(400);
                echo json_encode(['msg' => "Login $login already exists"], JSON_UNESCAPED_UNICODE); 

                $this->logActions('LOGIN already exists');
                exit;
            }
        } else {
            http_response_code(500);
            echo json_encode(['msg' => "smth went wrong"], JSON_UNESCAPED_UNICODE); 
            $this->logActions('DATABASE CHECK USER error');
            exit;
        }

        $query = "INSERT INTO USERS SET `name`=:name, `login` = :login, `pass_hash` = :pass_hash";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':name', $name);
        // $stmt->bindParam(':str_id', $uniqStr);

        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);

        $stmt->bindParam(':pass_hash', $pass_hash);

        if($stmt->execute()){
            // $user_id = $this->conn->lastInsertId();
            http_response_code(200);
            $this->user_token = TU\createTokens($this->conn, $login);
            echo json_encode(['msg' => "User $login was successfully registered!",
                                "token" => $this->user_token], JSON_UNESCAPED_UNICODE);
            $this->logActions('DATABASE USER REGISTRATION success');
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['msg' => "Unable to register user"], JSON_UNESCAPED_UNICODE);
            $this->logActions('DATABASE USER REGISTRATION error');
            exit;
        }
    }

}

$check = new registerUser();
$check->getRequest();

?>