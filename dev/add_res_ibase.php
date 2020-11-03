<?php
define('AccessProtect', true);
require_once(__DIR__.'/../utils/SystemDb.php');

$dbService = new DatabaseService();
$conn = $dbService->getConnection();

$data = json_decode(file_get_contents(__DIR__.'/ibase_res_ibase2.json'));


$start_index = 100000;
foreach($data as $key => $val){
    if ($val->OBJ_TP != 1){
        $query = "INSERT INTO RESOURCES (local_id, type, synindex, source, name, source_db_ref, source_db_id, lat, lng, height, param)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        

        $stmt = $conn->prepare($query);
        $lat = isset($val->LAT) ? $val->LAT : null;
        $lng = isset($val->LON) ? $val->LON : null;
        $local_id = $val->OBJ_USER_ID == "" || is_null($val->OBJ_USER_ID) ? 'mb_'.$start_index++ : 'mb_'.$val->OBJ_USER_ID."_".$start_index++;

        switch($val->OBJ_TP){
            case "5":
                $type = 4;
                break;
            case "6":
                $type = 5;
                break;
            case "7":
                $type = 6;
                break;
            case "8":
                $type = 7;
                break;
            case "9":
                $type = 8;
                break;
            case "10":
                $type = 9;
                break;
            case "11":
                $type = 10;
                break;                
            case "12":
                $type = 11;
                break;                
            case "13":
                $type = 12;
                break;                                                    
            case "14":
                $type = 13;
                break;                
            default:
                $type = 1;
        }
        $stmt->execute([$local_id, $type, $val->OBJ_USER_ID, 2, $val->OBJ_NAME, $val->OBJ_USER_ID, $val->OBJ_ID, $lat, $lng, null, null]);
    }
}

?>