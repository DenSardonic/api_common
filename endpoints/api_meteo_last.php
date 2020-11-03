<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;

class ApiLastMeteo extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'meteo_last';
    // protected $endpoint_name = 'debug';
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
            $temp['datetime'] = $val['datetime'];
            $temp['timezone'] = '+3';
            $temp['temperature_air'] = $val['temperature_air'];
            $temp['temperature_air_unit'] = $val['temperature_air_unit'];
            $temp['wind_speed'] = $val['wind_speed'];
            $temp['wind_speed_unit'] = $val['wind_speed_unit'];
            $temp['wind_speed_max'] = $val['wind_speed_max'];
            $temp['wind_speed_max_unit'] = $val['wind_speed_max_unit'];
            $temp['wind_direction'] = $val['wind_direction'];
            $temp['wind_direction_unit'] = $val['wind_direction_unit'];
            $temp['humidity'] = $val['humidity'];
            $temp['humidity_unit'] = $val['humidity_unit'];
            $temp['pressure'] = $val['pressure'];
            $temp['pressure_unit'] = $val['pressure_unit'];
            $temp['temperature_earth'] = $val['temperature_earth'];
            $temp['temperature_earth_unit'] = $val['temperature_earth_unit'];
            $temp['precipitations'] = $val['precipitations'];
            $temp['precipitations_unit'] = $val['precipitations_unit'];
            $response_data []= $temp;
        }

        $this->logActions('GET Last Meteo');
        // TODO check date(timestamp) an other timestamps timezome
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

        $scode = '10051,11001,11002,12101,12103,13011';
        $current_date = time();

        // PHP date() thinks, that timestamp is in UTC timezone, but it's already in local (+3)
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

        // print_r( $get_last_measures);
        // exit;

        // echo "data >>>";
        // print_r($data);
        // echo "data <<<";
        // exit;
        foreach ($data as $val){
            // $request_str = "http://10.3.1.30:8640/get?station={$val['source_db_id']}&codes=$scode&notbefore={$get_last_measures[$val['source_db_id']]}&local=1}";
            $request_str = "http://10.3.1.30:8640/get?station={$val['source_db_id']}&notbefore={$get_last_measures[$val['source_db_id']]}&notafter={$get_last_measures[$val['source_db_id']]}&local=1";
            
            $content = file_get_contents($request_str);
            if (TU\isGzipHeaderSet(TU\transformIntoHeaderMap($http_response_header))) {
                $content = gzdecode($content);
            }
            $req_data = json_decode($content);
            // $req_data = json_decode(file_get_contents($request_str));
            // echo $request_str;
            // print_r($req_data);
            // exit;
            // $params->moment -= $timezone_offset;
            $this->main_data[$val['resource_id']]['datetime'] = date("Y-m-d H:i:s",$get_last_measures[$val['source_db_id']]);
            foreach ($req_data as $param){
                switch ($param->code){
                    case '10051':
                        $this->main_data[$val['resource_id']]['pressure'] = $param->value/100;
                        $this->main_data[$val['resource_id']]['pressure_unit'] = 'hPa';
                    break;
                    // avg for 10 min 10 meters
                    case '11001':
                        $this->main_data[$val['resource_id']]['wind_direction'] = $param->value;
                        $this->main_data[$val['resource_id']]['wind_direction_unit'] = 'grad';
                    break;
                    case '11002':
                        switch ($param->meas_hash){
                            // max for 6 hours 10 meters
                            // case '2075830883':
                            //     $this->main_data[$val['resource_id']]['wind_speed'] = $param->value;
                            // break;
                            // max for 10 min 10 meters
                            case '478871789':
                                $this->main_data[$val['resource_id']]['wind_speed_max'] = $param->value;
                                $this->main_data[$val['resource_id']]['wind_speed_max_unit'] = 'm/s';
                            break;
                            // avg for 10 min 10 meters
                            case '1345858116':
                            default:
                                $this->main_data[$val['resource_id']]['wind_speed'] = $param->value;
                                $this->main_data[$val['resource_id']]['wind_speed_unit'] = 'm/s';
                        }
                    break;
                    // case '11002':
                    //     $this->main_data[$val['resource_id']]['wind_speed_max'] = $param->value;
                    // break;
                    case '12101':
                        $this->main_data[$val['resource_id']]['temperature_air'] = TU\getCeils($param->value, $param->unit);
                        $this->main_data[$val['resource_id']]['temperature_air_unit'] = 'C';
                    break;
                    case '12103':
                        // $this->main_data[$val['resource_id']]['pressure'] = $req_data->unit/100;
                    break;
                    case '13011':
                        $this->main_data[$val['resource_id']]['precipitations'] = $param->value;
                        $this->main_data[$val['resource_id']]['precipitations_unit'] = 'mm';
                    break;

                    // $this->main_data[$val['resource_id']]['level'] = $req_data->value;
                    // $this->main_data[$val['resource_id']]['unit'] = $req_data->unit;
                    // $this->main_data[$val['resource_id']]['datetime'] = date("Y-m-d H:i:s",$req_data->moment);
                    
                    // $this->main_data[$val['resource_id']]['temperature_air'] = $row['T'];
                    // $this->main_data[$val['resource_id']]['wind_speed'] = $row['V'];
                    // $this->main_data[$val['resource_id']]['wind_speed_max'] = $row['VM'];
                    // $this->main_data[$val['resource_id']]['wind_direction'] = $row['N'];
                    // $this->main_data[$val['resource_id']]['humidity'] = $row['W'];
                    // $this->main_data[$val['resource_id']]['pressure'] = $row['P'];
                    // $this->main_data[$val['resource_id']]['temperature_earth'] = $row['TG'];
                    // $this->main_data[$val['resource_id']]['precipitations'] = $row['R10'];
                }

            }
        }
        // print_r($this->main_data);
        // exit;


    }
    public function mainActionPgMeteo($data){
    }
    public function mainActionPgSts($val){
    }

    public function mainActionIbase($data){
        $temp_data = array();
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();

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
            $val['datetime'] = $row['DATETIME'];
            // print_r($row);
            // exit;
            if (!is_null($val['datetime'])){

                $query = "select a.t, a.v, a.vm, a.n, a.w, a.p, a.td
                    from amk a
                    where sobj=:obj and sdate_time = :datetime";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':sobj', $val['source_db_id']);
                $stmt->bindParam(':datetime', $val['datetime']);
                $stmt->execute();

                $row = $stmt->fetch();
            } else {
                $row['V'] = null;
                $row['WM'] = null;
                $row['N'] = null;
                $row['W'] = null;
                $row['P'] = null;
                $row['TG'] = null;
                $row['VR10'] = null;
            }

            // $this->main_data = $stmt->fetchAll();   
            $this->main_data[$val['resource_id']]['temperature_air'] = $row['T'];
            $this->main_data[$val['resource_id']]['temperature_air_unit'] = 'C';
            $this->main_data[$val['resource_id']]['wind_speed'] = $row['V'];
            $this->main_data[$val['resource_id']]['wind_speed_unit'] = 'm/s';
            $this->main_data[$val['resource_id']]['wind_speed_max'] = $row['VM'];
            $this->main_data[$val['resource_id']]['wind_speed_max_unit'] = 'm/s';
            $this->main_data[$val['resource_id']]['wind_direction'] = $row['N'];
            $this->main_data[$val['resource_id']]['wind_direction_unit'] = 'grad';
            $this->main_data[$val['resource_id']]['humidity'] = $row['W'];
            $this->main_data[$val['resource_id']]['humidity_unit'] = '%';
            $this->main_data[$val['resource_id']]['pressure'] = $row['P'];
            $this->main_data[$val['resource_id']]['pressure_unit'] = 'hPa';
            $this->main_data[$val['resource_id']]['temperature_earth'] = $row['TG'];
            $this->main_data[$val['resource_id']]['temperature_earth_unit'] = 'C';
            $this->main_data[$val['resource_id']]['precipitations'] = $row['R10'];
            $this->main_data[$val['resource_id']]['precipitations_unit'] = 'mm';
        }
    }

}

$endpoint_class = new ApiLastMeteo();
$endpoint_class->getRequest();


?>