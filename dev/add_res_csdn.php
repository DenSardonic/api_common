<?php
define('AccessProtect', true);
require_once(__DIR__.'/../utils/SystemDb.php');

$dbService = new DatabaseService();
$conn = $dbService->getConnection();

// $data = json_decode(file_get_contents(__DIR__.'/soap_stations.json'));
$data = json_decode(file_get_contents(__DIR__.'/csdn_res2.json'));


$start_index = 100000;
foreach($data as $key => $val){
    $cur_id = null;
    $query = "SELECT id FROM RESOURCES where source = 3 AND synindex = $val->index";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $cur_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    // echo $val->index." ".$cur_id.PHP_EOL;

    if (is_null($cur_id)){
        echo $val->index." ".$cur_id.PHP_EOL;

        $query = "INSERT INTO RESOURCES (local_id, type, synindex, source, name, source_db_ref, source_db_id, lat, lng, height, param)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($query);
        $lat = isset($val->lat) ? $val->lat : null;
        $lng = isset($val->lon) ? $val->lon : null;
        // $local_id = md5(uniqid($val->index."3", true));
        $local_id = 'sp_'.$val->index.'_'.$start_index++;
        $stmt->execute([$local_id, 1, $val->index, 3, $val->name, $val->index, $val->index, $lat, $lng, null, null]);
    }
}

?>