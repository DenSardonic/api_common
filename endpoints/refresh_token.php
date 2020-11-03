<?php 

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');
use Utils as TU;

class RefreshAllTokens extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'refresh_token';

    protected $userId;

    // public $refresh_token;
    // public $access_token;
    // public $cur_time;


    public function grantedAction(){
        $login = TU\cleanData(TU\getData('login'));

        http_response_code(200);
        $this->user_token = TU\createTokens($this->conn, $login);
        echo json_encode(['msg' => "Token for $login was successfully refreshed!",
                            "token" => $this->user_token], JSON_UNESCAPED_UNICODE);

        $this->logActions('TOKEN resresh success');
        exit;
    }

}

$check = new RefreshAllTokens();
$check->getRequest();




?>