<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');
require_once(__DIR__.'/../utils/ResourceDbSts.php');



use Utils as TU;

class ApiLastHydro extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'hydro_last';
    // protected $endpoint_name = 'meteo_last';

    public $resource_types = array();
    public $resources_info = array();
    public $resources_id = array();

    public $main_data = array();

    public $resource_sources = array();


    public function grantedAction(){
        $response_data = [];
        $this->getUserResources();

        $this->mainAction();
        
        foreach($this->main_data as $val){
            $temp = [];
            $temp['resource_name'] = $val['resource_name'];
            $temp['synindex'] = $val['synindex'];
            $temp['local_id'] = $val['resource_local_id'];
            $temp['resource_lat'] = $val['resource_lat'];
            $temp['resource_lng'] = $val['resource_lng'];
            $temp['datetime'] = isset($val['level']) ? $val['datetime'] : null;
            $temp['timezone'] = isset($val['level']) ? $val['timezone'] : null;
            // $temp['level'] = TU\getMm($val['level'], $val['unit']);
            $temp['level'] = isset($val['level']) ? ceil($val['level']) : null;
            $temp['is_baltic'] = true;
            // $temp['level'] = $val['level'];
            // $temp['unit'] = $val['unit'];
            // $temp['unit'] = isset($val['unit']) ? 'mm' : null;
            $temp['unit'] = isset($val['level']) ? 'mm' : null;
            $response_data []= $temp;
        }

        $this->logActions('GET Last Hydro');

        http_response_code(200);
        echo json_encode($response_data, JSON_UNESCAPED_UNICODE );
        // echo json_encode($this->resources_id, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function getUserResources(){
        // check binded types if there is no limitation for user or 
        // there is a whole type binded for user
        $query = "SELECT ert.resource_type as resource_type
                from endpoints_resources_types as ert
                where ert.endpoint_id = :endpoint_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':endpoint_id', $this->endpoint_id);

        $stmt->execute();
        while($row = $stmt->fetch()){
            $this->resource_types []= $row['resource_type'];
        }

        // if resources instanses are binded
        $query = "SELECT ar.resource_id as resource_id, ar.resource_local_id as resource_local_id,
            ar.datetime_from as datetime_from, ar.datetime_to as datetime_to,
            r.name as resource_name, s.name as source_name, r.source_db_id as source_db_id,
            r.lat as resource_lat, r.lng as resource_lng, r.synindex as synindex
            from access_resource as ar
            left join resources as r on r.id = ar.resource_id
            left join resources_types as rt on rt.id = r.type
            left join sources as s on s.id = r.source
            where ar.endpoint_id = :endpoint_id and ar.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':endpoint_id', $this->endpoint_id);
        $stmt->bindParam(':user_id', $this->userId);

        // echo " $this->endpoint_id $this->userId";
        // exit;

        $stmt->execute();
        while($row = $stmt->fetch()){
            $temp = [];
            $temp['resource_id'] = $row['resource_id'];
            $temp['resource_local_id'] = $row['resource_local_id'];
            $temp['synindex'] = $row['synindex'];
            $temp['datetime_from'] = $row['datetime_from'];
            $temp['datetime_to'] = $row['datetime_to'];
            $temp['source_db_id'] = $row['source_db_id'];
            $temp['resource_name'] = $row['resource_name'];
            $temp['source_name'] = $row['source_name'];
            $temp['resource_lat'] = $row['resource_lat'];
            $temp['resource_lng'] = $row['resource_lng'];
            // $this->resources_info[$row['source_db_id']] = $temp;
            $this->resources_id []= $row['source_db_id'];
            if (!isset($this->resource_sources[$row['source_name']]))
                $this->resource_sources[$row['source_name']] = array();
            $this->resource_sources[$row['source_name']][$row['resource_id']] = $temp; 
            $this->main_data[$row['resource_id']] = $temp;
        }
    }

    public function mainAction(){

        $this->mainActionIbase($this->resource_sources['ibase_meteo']);
        $this->mainActionCsdn($this->resource_sources['csdn_spb']);
        $this->mainActionPgMeteo($this->resource_sources['pg']);
        $this->mainActionPgSts($this->resource_sources['sts']);

    }

    public function mainActionCsdn($data){
        $scode = '13205'; // need to substruct 5000 mm
        $current_date = time();
        
        // PHP thinks, that timestamp is in UTC timezone, but it's already in local (+3)
        // so to make it not disappointed, reduce result for $timezone_offset value
        $timezone_offset = 3 * 60 * 60;
        
        $get_last_measures = [];
        $id_array = [];

        $request_str = "http://10.3.1.30:8640/stations.json";
        // ! o-o may be its better to search last data for the period back from current date
        $content = file_get_contents($request_str);
        if (TU\isGzipHeaderSet(TU\transformIntoHeaderMap($http_response_header))) {
            $content = gzdecode($content);
        }
        $stations = json_decode($content);
        // $stations = json_decode(gzdecode(file_get_contents($request_str)));

        foreach ($data as $val){
            $id_array []= $val['source_db_id'];
        }

        foreach ($stations as $val){
            if (in_array($val->sindex, $id_array))
                $get_last_measures[$val->sindex] = $val->last_moment;
        }

        // echo "data >>>";
        // print_r($data);
        // echo "data <<<";
        // exit;
        foreach ($data as $val){
            $request_str = "http://10.3.1.30:8640/get?station={$val['source_db_id']}&unit=mm&limit=1&codes=$scode&notbefore={$get_last_measures[$val['source_db_id']]}&local=1";
            
            $content = file_get_contents($request_str);
            if (TU\isGzipHeaderSet(TU\transformIntoHeaderMap($http_response_header))) {
                $content = gzdecode($content);
            }
            $req_data = json_decode($content)[0];
            $req_data->moment -= $timezone_offset;

            // $req_data = json_decode(file_get_contents($request_str))[0];
            // $this->main_data[$val['resource_id']]['level'] = $req_data->value;
            $this->main_data[$val['resource_id']]['level'] = $req_data->code == '13205' ? $req_data->value - 5000 : $req_data->value;
            $this->main_data[$val['resource_id']]['unit'] = $req_data->unit;
            $this->main_data[$val['resource_id']]['timezone'] = "+3";
            $this->main_data[$val['resource_id']]['datetime'] = isset($req_data->moment) ? date("Y-m-d H:i:s",$req_data->moment) : null;

            // print_r($req_data);
        }


    }
    public function mainActionPgMeteo($data){
    }
    public function mainActionPgSts($data){
        $db = new stsResDatabase();
        $conn = $db->getConnection();

        // die("dd");
        // print_r($data);
        // exit;
        foreach ($data as $key => $val){
        $row = array();
        // GET last measures
        // $query = "select d.data_meta_id, d.data_datatyp_id, d.data_valueraw, d.data_valueend, d.data_timemeas
        //             from tbldata as d
        //             left join tblmeta as m on m.meta_id = d.data_meta_id
        //             left join tbllogger as l on l.log_id = m.meta_log_id
        //             where l.log_id = :sobj and d.data_timemeas = (
        //                 SELECT max(dd.data_timemeas)
        //                 from tbldata as dd
        //                 left join tblmeta as mm on mm.meta_id = dd.data_meta_id
        //                 left join tbllogger as ll on ll.log_id = mm.meta_log_id
        //             where ll.log_id = :sobj
        // )";

        // MEGA COOL REQUEST!!!! GET last measures
        $descr_lvl = "Уровень";

        $query = "select DISTINCT ON (d.data_meta_id)
            d.data_meta_id as meta_id, d.data_datatyp_id as datatyp, d.data_valueraw as valraw, 
            d.data_valueend as val, d.data_timemeas as datetime, 
            t.datatyp_descr as descr, t.datatyp_unit as unit
                from tbldata as d
                left join tblmeta as m on m.meta_id = d.data_meta_id
                left join tbllogger as l on l.log_id = m.meta_log_id
                left join tbldatatyp as t on t.datatyp_id = d.data_datatyp_id
                where l.log_id = :sobj and t.datatyp_descr = :descr_lvl
                order by d.data_meta_id, d.data_timemeas DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':sobj', $val['source_db_id']);
            $stmt->bindParam(':descr_lvl', $descr_lvl);
            $stmt->execute();

            while ($row = $stmt->fetch()){
                // I know it's silly, but if it needs to add more params...
                switch ($row['descr']){
                    case "Уровень":
                        $this->main_data[$val['resource_id']]['level'] = TU\getMm($row['val'], $row['unit']);
                        $this->main_data[$val['resource_id']]['unit'] = 'mm';
                        $this->main_data[$val['resource_id']]['datetime'] = $row['datetime'];
                        // ? or UTC??
                        $this->main_data[$val['resource_id']]['timezone'] = "+3";

                    break;
                }
            }

        }

    }

    public function mainActionIbase($data){
        // $temp_data = array();
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

        // print_r($data);
        // exit;
        foreach ($data as $key => $val){
            $row = array();
            $row['datetime'] = null;
            $query = "select f.sobj, cast(f.smax_day || '.' || f.smax_month || '.' || f.smax_year || ' ' || f.smax_hour || ':' || f.smax_minute as timestamp) as datetime
                from stable_fill f  
                where f.sobj = :sobj";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':sobj', $val['source_db_id']);

            $stmt->execute();
            
            $row = $stmt->fetch();
            $this->main_data[$val['resource_id']]['datetime'] = $row['DATETIME'];
            $this->main_data[$val['resource_id']]['unit'] = 'mm';

            $val['datetime'] = $row['DATETIME'];
            // print_r($row);
            // exit;
            if (!is_null($val['datetime'])){

                $query = "select l.lvl, l.rec_lvl
                    from levels l
                    where l.sobj=:sobj and l.sdate_time = :datetime";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':sobj', $val['source_db_id']);
                $stmt->bindParam(':datetime', $val['datetime']);
                $stmt->execute();

                $row = $stmt->fetch();
            } else {
                $row['LVL'] = null;
                $row['REC_LVL'] = null;

            }
            $this->main_data[$val['resource_id']]['level'] = $row['LVL'];
            $this->main_data[$val['resource_id']]['rec_lvl'] = $row['REC_LVL'];
            $this->main_data[$val['resource_id']]['timezone'] = "+3";

        }
    }

}

$endpoint_class = new ApiLastHydro();
$endpoint_class->getRequest();


?>