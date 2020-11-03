<?php
define('AccessProtect', true);
require_once(__DIR__.'/../utils/ResourceDbIbase.php');

$db = new ibaseResDatabase();
$conn = $db->getConnection();




// $query = "SELECT s.obj_id, sui.obj_user_id, s.obj_name, s.obj_tp, s.lat, s.lon
//             FROM SOBJECTS s
//             left join SOBJECT_USER_IDS sui on s.obj_id = sui.obj_id
//             where sui.obj_id_type = 1 and s.obj_tp <> 1";
$query = "SELECT * FROM SOBJECT_TYPES";


$stmt = $conn->prepare($query);
// $lat = isset($val->LAT) ? $val->LAT : null;
// $lng = isset($val->LON) ? $val->LON : null;
// $local_id = md5(uniqid($val->OBJ_ID."2", true));
$stmt->execute();

$data = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_UNICODE);

file_put_contents('ibase_res_ibase2.json', $data);


?>