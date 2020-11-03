<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once(__DIR__.'/../utils/ApiSingleResourceAccessCheck.php');
// require_once(__DIR__.'/../utils/ResourceDbIbase.php');
require_once(__DIR__.'/../utils/ResourceDbSts.php');
// require_once(__DIR__.'/../utils/ResourceDbMysql.php');



use Utils as TU;

class ApiStsXml extends ApiSingleResourceAccessCheck{
    // resources_is_accessed_list is all what I need
    protected $endpoint_name = 'sts_xml';
    protected $resource_type = 'sts_xml';

    // public $response_data = array();
    public $xml_message = "";
    public $last_datetime = "";

    public function getRequestedResources(){

        $this->mainAction();

        $this->response_data = [
            "msg" => "",
            "station" => [
                'synindex' => $this->local_resource['synindex'],
                'name' => $this->local_resource['resource_name'],
                'lat' => $this->local_resource['lat'],
                'lng' => $this->local_resource['lng'],
                'timezone' => '+3',
                'dataset' => array(),
                'last_datetime' => $this->last_datetime,
                'xml' => $this->xml_message
                ]
        ];

        http_response_code(200);
        echo json_encode($this->response_data, JSON_UNESCAPED_UNICODE );
        // echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function mainAction(){
                $this->mainActionPgSts($this->local_resource['source_db_id']);

    }


    public function mainActionPgSts($resource_id){

        $db = new stsResDatabase();
        $conn = $db->getConnection();

        $SAfter =  date('Y-m-d H:i:s', $this->requested_from);
        $SBefore = date('Y-m-d H:i:s', $this->requested_to);

        $query = "select 
            d.data_meta_id as meta_id, d.data_datatyp_id as datatyp, d.data_valueraw as valraw, 
            d.data_valueend as val, d.data_timemeas as datetime, 
            t.datatyp_descr as descr, t.datatyp_unit as unit
                from tbldata as d
                left join tblmeta as m on m.meta_id = d.data_meta_id
                left join tbllogger as l on l.log_id = m.meta_log_id
                left join tbldatatyp as t on t.datatyp_id = d.data_datatyp_id
                where l.log_id = :sobj and t.datatyp_descr in (:descr_lvl, :descr_tmp)
                and d.data_timemeas between :after and :before
                order by d.data_timemeas DESC";
        $stmt = $conn->prepare($query);
        $descr_lvl = "Уровень";
        $descr_tmp = "Тводы";
        $stmt->bindParam(':sobj', $resource_id);
        $stmt->bindParam(':after', $SAfter);
        $stmt->bindParam(':before', $SBefore);
        $stmt->bindParam(':descr_lvl', $descr_lvl);
        $stmt->bindParam(':descr_tmp', $descr_tmp);
        $stmt->execute();

        $datetime_arr = [];
        $counter = 0;
        while ($row = $stmt->fetch()){
            if ($counter === 0){
                $this->last_datetime = date("Y-m-d H:i:s",strtotime($row['datetime']));
            }
            // $datetime = date("Y-m-d\TH:i:s+03:00",strtotime($row['datetime']));
            $datetime = date("d-m-Y\TH:i:s+03:00",strtotime($row['datetime']));

            $datetime_arr[$datetime]['datetime'] = $datetime;
            $datetime_arr[$datetime]['type'] = 'AGK';

            switch ($row['descr']){
                case $descr_lvl:
                    $datetime_arr[$datetime]['level'] = TU\getMm($row['val'], $row['unit']);
                    // $datetime_arr[$row['datetime']]['level_unit'] = $row['unit'];
                    $datetime_arr[$datetime]['level_unit'] = 'mm';
                break;
                case $descr_tmp:
                    $datetime_arr[$datetime]['temperature'] = TU\getCeils($row['val'], $row['unit']);
                    // $datetime_arr[$row['datetime']]['temperature_unit'] =  $row['unit'];
                    $datetime_arr[$datetime]['temperature_unit'] =  'C';
                break;
            }

            $counter++;
        }

        $this->xml_message = '<?xml version="1.0" encoding="utf-8" ?>
            <message>';
        $this->xml_message .= '<station ID="'.$this->local_resource['synindex'].'">';

        foreach($datetime_arr as $key => $val){
            $this->xml_message .= '<report TIME="'.$key.'">';
            $this->xml_message .= '                
                <parameter VAR="LW" unit="mm">  
                    <value>'.$val['level'].'</value>
                </parameter>
                <parameter VAR="TW" unit="c">  
                    <value>'.$val['temperature'].'</value>
                </parameter>';
            $this->xml_message .= '</report>';
        }
        $this->xml_message .= '</station>';
        $this->xml_message .= '</message>';

    }

}

$res = new ApiStsXml();
$res->getRequest();


?>