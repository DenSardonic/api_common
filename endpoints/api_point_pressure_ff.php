<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once(__DIR__.'/../utils/ApiSingleResourceAccessCheck.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');
require_once(__DIR__.'/../utils/ResourceDbSts.php');
require_once(__DIR__.'/../utils/ResourceDbMysql.php');



use Utils as TU;

class ApiPointPressure extends ApiSingleResourceAccessCheck{
    // resources_is_accessed_list is all what I need
    protected $endpoint_name = 'point_pressure';
    protected $resource_type = 'point_pressure';

    public $response_data = array();


    public function getRequestedResources(){

        $this->response_data = [
            "msg" => "",
            "station" => [
                'synindex' => $this->local_resource['synindex'],
                'name' => $this->local_resource['resource_name'],
                'lat' => $this->local_resource['lat'],
                'lng' => $this->local_resource['lng'],
                'pressure' => '',
                'pressure_unit' => '',
                'datatime' => '',
                'timezone' => ''
            ]
        ];

        $this->mainAction();

        http_response_code(200);
        echo json_encode($this->response_data, JSON_UNESCAPED_UNICODE );
        // echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function mainAction(){
        switch ($this->local_resource['source_name']){
            case 'csdn_spb':
                $this->mainActionCsdn($this->local_resource['source_db_id']);
            break;
            case 'ibase_meteo':
                $this->mainActionIbase($this->local_resource['source_db_id']);
            break;
            // case 'pg':
            //     $this->mainActionPgMeteo($this->resource_id);
            // break;
            case 'sts':
                $this->mainActionPgSts($this->local_resource['source_db_id']);
            break;
            case 'rst_mysql':
                $this->mainActionMySms($this->local_resource['source_db_id']);
            break;

        }

    }
    public function mainActionCsdn($resource_id){
        // $lvl_code = '13205';
        // $temp_code = '13082';

        // // PHP date() thinks, that timestamp is in UTC timezone, but it's already in local (+3)
        // // so to make it not disappointed, reduce result for $timezone_offset value
        // $timezone_offset = 3 * 60 * 60;

        // $stream_auto = 1;
        // $stream_manual = 0;

        // $request_str = "http://10.3.1.30:8640/get?station=$resource_id"
        //     ."&codes=$lvl_code,$temp_code&notbefore={$this->requested_from}&notafter={$this->requested_to}&local=1";

        // // echo $request_str;
        // // exit;
        // $content = file_get_contents($request_str);
        // if (TU\isGzipHeaderSet(TU\transformIntoHeaderMap($http_response_header))) {
        //     $content = gzdecode($content);
        // }
        // $req_data = json_decode($content);

        // foreach($req_data as $val){
        //     $val->moment -= $timezone_offset;
        //     $datetime_arr[$val->moment.$val->stream]['datetime'] = date("Y-m-d H:i:s",$val->moment);
        //     switch ($val->stream){
        //         case $stream_auto:
        //             $datetime_arr[$val->moment.$val->stream]['type'] = 'AGK';
        //         break;
        //         case $stream_manual:
        //             $datetime_arr[$val->moment.$val->stream]['type'] = 'MANUAL';
        //         break;
                
        //     }

        //     switch ($val->code){
        //         case $lvl_code:
        //             $datetime_arr[$val->moment.$val->stream]['level'] = TU\getMm($val->value, $val->unit);
        //             // $datetime_arr[$val->moment.$val->stream]['level_unit'] = $val->unit;
        //             $datetime_arr[$val->moment.$val->stream]['level_unit'] = 'mm';
        //         break;
        //         case $temp_code:
        //             $datetime_arr[$val->moment.$val->stream]['temperature'] = TU\getCeils($val->value, $val->unit);
        //             // $datetime_arr[$val->moment.$val->stream]['temperature_unit'] =  $val->unit;
        //             $datetime_arr[$val->moment.$val->stream]['temperature_unit'] =  'C';
        //         break;
        //     }

        // }
        // $this->response_data['station']['dataset'] = array_values($datetime_arr);

        // // foreach($req_data as $val){
        // //     $temp = array();

        // //     $temp['datetime'] = date("Y-m-d H:i:s",$val->moment);
        // //     $temp['level'] = $val->value;
        // //     $temp['level_unit'] = $val->unit;
        // //     $temp['temperature'] = null;
        // //     $temp['temperature_unit'] = null;
    
        // //     $this->response_data['station']['dataset'] []= $temp;
        // // }

        // $this->response_data['station']['timezone'] = '+3';

    }


    public function mainActionIbase($resource_id){

        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

        $SAfter =  date('Y-m-d H:i:s', $this->requested_from);
        $SBefore = date('Y-m-d H:i:s', $this->requested_to);

        $query =  "select first 1 from 
            from levels l
            where l.sobj = :sind and l.sdate_time between :after and :before 
            order by l.sdate_time";


        $stmt = $conn->prepare($query);
        $stmt->bindParam(':sind', $resource_id);
        $stmt->bindParam(':after', $SAfter);
        $stmt->bindParam(':before', $SBefore);

        // echo "SAfter $SAfter SBefore $SBefore";
        // exit;

        $stmt->execute();


        while ($row = $stmt->fetch()){
                $temp = array();
    
                $temp['datetime'] = $row['SDATE_TIME'];
                $temp['station'] = $row['SDATE_TIME'];
                $temp['level'] = $row['LVL'];
                $temp['level_unit'] = "mm";
                $temp['temperature'] = null;
                $temp['temperature_unit'] = null;
                $temp['type'] = 'AGK';

                $this->response_data['station']['dataset'] []= $temp;

        }
        $this->response_data['station']['timezone'] = '+3';      

    }


    public function mainActionPgSts($resource_id){

        // $db = new stsResDatabase();
        // $conn = $db->getConnection();

        // $SAfter =  date('Y-m-d H:i:s', $this->requested_from);
        // $SBefore = date('Y-m-d H:i:s', $this->requested_to);


        // $query = "select 
        //     d.data_meta_id as meta_id, d.data_datatyp_id as datatyp, d.data_valueraw as valraw, 
        //     d.data_valueend as val, d.data_timemeas as datetime, 
        //     t.datatyp_descr as descr, t.datatyp_unit as unit
        //         from tbldata as d
        //         left join tblmeta as m on m.meta_id = d.data_meta_id
        //         left join tbllogger as l on l.log_id = m.meta_log_id
        //         left join tbldatatyp as t on t.datatyp_id = d.data_datatyp_id
        //         where l.log_id = :sobj and t.datatyp_descr in (:descr_lvl, :descr_tmp)
        //         and d.data_timemeas between :after and :before
        //         order by d.data_timemeas DESC";
        // $stmt = $conn->prepare($query);
        // $descr_lvl = "Уровень";
        // $descr_tmp = "Тводы";
        // $stmt->bindParam(':sobj', $resource_id);
        // $stmt->bindParam(':after', $SAfter);
        // $stmt->bindParam(':before', $SBefore);
        // $stmt->bindParam(':descr_lvl', $descr_lvl);
        // $stmt->bindParam(':descr_tmp', $descr_tmp);
        // $stmt->execute();

        // $datetime_arr = [];
        // while ($row = $stmt->fetch()){

        //     $datetime_arr[$row['datetime']]['datetime'] = $row['datetime'];
        //     $datetime_arr[$row['datetime']]['type'] = 'AGK';

        //     switch ($row['descr']){
        //         case $descr_lvl:
        //             $datetime_arr[$row['datetime']]['level'] = TU\getMm($row['val'], $row['unit']);
        //             // $datetime_arr[$row['datetime']]['level_unit'] = $row['unit'];
        //             $datetime_arr[$row['datetime']]['level_unit'] = 'mm';
        //         break;
        //         case $descr_tmp:
        //             $datetime_arr[$row['datetime']]['temperature'] = TU\getCeils($row['val'], $row['unit']);
        //             // $datetime_arr[$row['datetime']]['temperature_unit'] =  $row['unit'];
        //             $datetime_arr[$row['datetime']]['temperature_unit'] =  'C';
        //         break;
        //     }


        // }
        // $this->response_data['station']['dataset'] = array_values($datetime_arr);

        // $this->response_data['station']['timezone'] = '+3';

    }

    public function mainActionMySms($resource_id){

        // $db = new MysqlSmsDatabase();
        // $conn = $db->getConnection();

        // $SAfter =  date('Y-m-d H:i:s', $this->requested_from);
        // $SBefore = date('Y-m-d H:i:s', $this->requested_to);


        // $query = "select d.lvl, d.temperature, d.data_date as SDATE_TIME
        //         from lvl_data as d
        //         where d.station_id = :sobj AND isCorrect = 1 AND d.data_date between :after and :before
        //         order by d.data_datetime DESC";
        // // echo " $query   resource_id $resource_id SAfter $SAfter SBefore $SBefore";
        // // exit;
        
        // $stmt = $conn->prepare($query);
        // $stmt->bindParam(':sobj', $resource_id);
        // $stmt->bindParam(':after', $SAfter);
        // $stmt->bindParam(':before', $SBefore);
        // $stmt->execute();


        // while ($row = $stmt->fetch()){
        //     // print_r($row);
        //     $temp = array();
    
        //     $temp['datetime'] = $row['SDATE_TIME'];
        //     $temp['level'] = $row['lvl'];
        //     $temp['level_unit'] = "mm";
        //     $temp['temperature'] = $row['temperature']/10;
        //     $temp['temperature_unit'] = "C";
        //     $temp['type'] = 'MANUAL';

        //     $this->response_data['station']['dataset'] []= $temp;

        // }

        // $this->response_data['station']['timezone'] = '+3';

    }
    public function mainActionPgMeteo($data){
        // $full_result = [
        //     "msg" => "",
        //     "stations" => array(),
        //     "type" => "pg",
        //     "status" => 0 
        // ];
        // $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        // return $full_result;
    }

}

$res = new ApiPointPressure();
$res->getRequest();


?>