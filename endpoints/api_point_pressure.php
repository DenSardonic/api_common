<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once(__DIR__.'/../utils/ApiEndpointAccessCheck.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;

class ApiPointPressure extends ApiEndpointAccessCheck{
    protected $endpoint_name = 'point_pressure';
    // protected $endpoint_name = 'debug';

    public $anchor_stations = [
        'ne' => [
            '22305' => ['synid' => '22305', 'type' => 'ne', 'order' => 1], 
            '22413' => ['synid' => '22413', 'type' => 'ne', 'order' => 2], 
            '22408' => ['synid' => '22408', 'type' => 'ne', 'order' => 3], 
            '22511' => ['synid' => '22511', 'type' => 'ne', 'order' => 4], 
            '22829' => ['synid' => '22829', 'type' => 'ne', 'order' => 5]
        ],
        'w' => [
            '22907' => ['synid' => '22907', 'type' => 'w', 'order' => 1], 
            '86071' => ['synid' => '86071', 'type' => 'w', 'order' => 2], 
            '26157' => ['synid' => '26157', 'type' => 'w', 'order' => 3], 
            '26071' => ['synid' => '26071', 'type' => 'w', 'order' => 4], 
            '26059' => ['synid' => '26059', 'type' => 'w', 'order' => 5]
        ],
        's' => [
            '26477' => ['synid' => '26477', 'type' => 's', 'order' => 1], 
            '26378' => ['synid' => '26378', 'type' => 's', 'order' => 2], 
            '26462' => ['synid' => '26462', 'type' => 's', 'order' => 3], 
            '26359' => ['synid' => '26359', 'type' => 's', 'order' => 4], 
            '26167' => ['synid' => '26167', 'type' => 's', 'order' => 5]
        ]
    ];

    public $chosen_station_data = [];
    public $chosen_stations = [];

    //Shlisselburg
    //54 sp_26072_100053 1 26072 3 Шлиссльбург 26072 26072 59.933000 31.000000
    public $anchor_station = [
        'name' => 'Шлиссльбург',
        'id' => 'sp_26072_100053',
        'synindex' => '26072',
        'id_db' => '26072',
        'lat' => '59.933000',
        'lon' => '31.000000',
    ];

    // public $anchor_stations = ['22305', '22907', '26477'];

    // public $triangle_data = [];

    public $response_data = [];

    public $requested_point = [
        'lat' => null, 
        'lon' => null, 
        // 'synindex' => null, 
        'datetime' => null];

    public $main_data = array();

    // public $dist_array = array();
    // public $station_type = null;

    public $stations_data = [];

    public function grantedAction(){
        $this->requested_point['lat'] = TU\cleanData(TU\getData('lat'));
        $this->requested_point['lng'] = TU\cleanData(TU\getData('lng'));
        // $this->requested_point['synindex'] = TU\cleanData(TU\getData('synindex'));
        $this->requested_point['datetime'] = TU\cleanData(TU\getData('datetime'));

        $this->response_data = [
            'msg' => '',
            'lat' => $this->requested_point['lat'],
            'lng' => $this->requested_point['lng'],
            'pressure' => '',
            'pressure_unit' => '',
            'stations_for_interpolation' => []
        ];

        $this->mainAction();
        

        $this->logActions("GET pressure", "lat: {$this->requested_point['lat']}, lng: {$this->requested_point['lng']}");
        // TODO check date(timestamp) an other timestamps timezome
        http_response_code(200);
        echo json_encode($this->response_data, JSON_UNESCAPED_UNICODE );
        // echo json_encode($this->resources_id, JSON_UNESCAPED_UNICODE );
        exit;
    }
    
    public function mainAction(){
        $pressure_value = null;
        // $stations_data = [];

        
        // Get stations info by synindex
        foreach ($this->anchor_stations as $type => $stations_val){
            $temp = [];
            foreach ($stations_val as $st => $val){
                $temp[$st] = $this->getId($st);
            }
            $this->stations_data[$type] = $temp;
        }
        // print_r($this->stations_data);
        // exit;

        // Get data for all anchor stations 
        // Chose latest data for every station

        // TODO adjust to one unit
        foreach ($this->stations_data as $type => $stations_val){
            foreach ($stations_val as $st => $val){
                if (count($val) > 0) {
                    foreach ($val as $key => $sinfo){
                        switch ($sinfo['source']){
                            // CSDN
                            case 3:
                                $this->stations_data[$type][$st][$key]['data'] = $this->getLastPressureCsdn($sinfo['source_db_id']);
                            break;
                            // meteobase
                            case 2:
                                $this->stations_data[$type][$st][$key]['data'] = $this->getLastPressureMb($sinfo['source_db_id']);
                            break;
                            // case 3:
                            // break;
                        }
                    }
                    // choose latest data for every station. Among different sources
                    $latest_datetime = 0;
                    $latest_key = 0;
                    foreach ($val as $key => $sinfo){
                        if ($sinfo['data'] != null && $sinfo['data']['value'] != null && $sinfo['data']['datetime'] >= $latest_datetime){
                            $latest_datetime = $sinfo['data']['datetime'];
                            $latest_key = $key;
                        }
                    }
                    $this->chosen_station_data[$type][$st] = $this->stations_data[$type][$st][$latest_key];
                }
            } 
        }    

        // print_r($this->chosen_station_data);
        // exit;

        // !no if latest data for all thre types is not older than 1 hour, then use it in interpolation 
        // !no with status "ok"
        // !no else, use last with status "old data"
        // get lastest data for every station type
        foreach ($this->chosen_station_data as $type => $st_val){
            $latest_datetime = 0;
            $latest_key = 0;
            foreach ($st_val as $key => $sinfo){
                if ($sinfo['data'] != null && $sinfo['data']['value'] != null && $sinfo['data']['datetime'] >= $latest_datetime){
                    $latest_datetime = $sinfo['data']['datetime'];
                    $latest_key = $key;
                }
            }
            $this->chosen_stations[$type] = $this->chosen_station_data[$type][$latest_key];

        }
        // print_r($this->chosen_stations);
        // exit;

        $stations_arr = [
            $this->chosen_stations['ne']['synindex'],
            $this->chosen_stations['w']['synindex'],
            $this->chosen_stations['s']['synindex']
        ];
        //interpolation!!!!! Yooo-hoooo! 
        $x1 = $this->chosen_stations['ne']['lat'];
        $y1 = $this->chosen_stations['ne']['lng'];
        $v1 = $this->chosen_stations['ne']['data']['value'];
        $x2 = $this->chosen_stations['w']['lat'];
        $y2 = $this->chosen_stations['w']['lng'];
        $v2 = $this->chosen_stations['w']['data']['value'];
        $x3 = $this->chosen_stations['s']['lat'];
        $y3 = $this->chosen_stations['s']['lng'];
        $v3 = $this->chosen_stations['s']['data']['value'];
        $x = $this->requested_point['lat'];
        $y = $this->requested_point['lng'];
        $pressure_value = TU\valLinearInterpolation($x1, $y1, $v1, $x2, $y2, $v2, $x3, $y3, $v3, $x, $y);

        $this->response_data['stations_for_interpolation'] = $stations_arr ;
        $this->response_data['pressure'] = $pressure_value ;
        $this->response_data['pressure_unit'] = 'pa' ;
        

    }
    
    public function getId($synindex){
        $query = "SELECT r.id, r.synindex,  r.local_id, r.type, r.source, r.name, r.source_db_id, r.lat, r.lng
                from resources as r 
                where r.synindex = :synindex
            ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':synindex', $synindex);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getLastPressureCsdn($id){

        $scode = '10051'; //pressure
        $current_datetime = time(); // all in UTC
        $c_datetime_hour = $current_datetime - 6*60*60;


        $request_str = "http://10.3.1.30:8640/get?stream=1&stations={$id}&codes={$scode}&notbefore={$c_datetime_hour}&notafter={$current_datetime}&local=0";
        
        $content = file_get_contents($request_str);
        if (TU\isGzipHeaderSet(TU\transformIntoHeaderMap($http_response_header))) {
            $content = gzdecode($content);
        }
        $req_data = json_decode($content);

        usort($req_data, 'TU\cmp_moments');
        $temp = [];

        // $temp['datetime'] = $req_data[0]->moment;
        $temp['datetime'] = $req_data[0]->point_at;
        $temp['value'] = $req_data[0]->value;
        $temp['unit'] = $req_data[0]->unit;
        $temp['inf'] = "stream: {$req_data[0]->stream}";

        // print_r($this->main_data);
        // exit;
        
        return $temp;

    }

    public function getLastPressureMb($id = null){
        return ['datetime' => null, 'value' => null, 'unit' => null, 'inf' => ''];
    }


    public function getAllRadius(){

        $query = "SELECT ae.id as id, ae.datetime_from as dt_from, ae.datetime_to as dt_to, 
                        ae.dont_check_res_accsess as dont_check_res_accsess,
                        ae.check_only_type as check_only_type
                FROM  ACCESS_ENDPOINT as ae
                where ae.endpoint_id = :e_id and ae.user_id = :user_id";
        // $query = "SELECT id from USERS where auth_token=:auth_token LIMIT 0, 1";
        $stmt = $this->conn->prepare($query);
        // $stmt->bindParam(':auth_token', $this->auth_token);
        $stmt->bindParam(':e_id', $this->endpoint_id);
        $stmt->bindParam(':user_id', $this->userId);

        $this->dist_array = "";

    }

}

$endpoint_class = new ApiPointPressure();
$endpoint_class->getRequest();


?>