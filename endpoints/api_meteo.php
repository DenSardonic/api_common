<?php

require_once(__DIR__.'/../utils/ApiResource.php');
require_once(__DIR__.'/../utils/ResourceDbIbase.php');


use Utils as TU;
// ! OOOOLD
class ApiMeteo extends ApiResource{
    // resources_is_accessed_list is all what I need
    protected $endpoint_name = 'meteo';
    protected $resource_type = 'meteo';

    protected $sources_list = ['pg', 'ibase_meteo', 'csdn_spb', 'sts'];
    protected $sources_resources = [];



    public function getRequestedResources(){
        $response = [
            "msg" => "",
            "stations" => array()
        ];
        $message = "";

        foreach ($this->resources_is_accessed_list as $key => $resource){
            $source_type = $resource['source_name'];
            if (!isset($this->sources_resources[$source_type]))
                $this->sources_resources[$source_type] = [];
            
            // $this->sources_resources[$source_type] []= $key;
            $this->sources_resources[$source_type] []= $resource['resource_id'];
            // $this->sources_resources[$source_type] []= $resource['resource_local_id'];
        }

        foreach($this->sources_resources as $key => $ids_list){
            $res = ['status' => 0];
            switch ($key){
                case 'csdn_spb':
                    $res = $this->getCsdn($ids_list);
                    break;
                case 'ibase_meteo':
                    $res = $this->getIbase($ids_list);
                    break;
                case 'sts':
                    $res = $this->getSts($ids_list);
                    break;
                case 'pg':
                    $res = $this->getPg($ids_list);
                    break;        
            }
            if ($res['status'])
                $response['stations']  []= $res['stations'];        
        }

        $response['msg'] = $message;

        http_response_code(200);
        // echo json_encode($this->resources_is_accessed_list, JSON_UNESCAPED_UNICODE );
        echo json_encode($response, JSON_UNESCAPED_UNICODE );
        exit;
    }

    public function getCsdn($ids_list){
        // $client = new SoapClient(SOAP_STR);
        // $scode = '13205';
        // $sind = '';
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "csdn_spb",
            "status" => 0 
        ];

        // PHP date() thinks, that timestamp is in UTC timezone, but it's already in local (+3)
        // so to make it not disappointed, reduce result for $timezone_offset value
        $timezone_offset = 3 * 60 * 60;

        // // ? not in one list in a case of different time access
        // foreach ($ids_list as $id){
        //     $sind = $this->resources_is_accessed_list[$id]['source_db_ref'];
        //     $SBefore = date('Y-m-d\TH:i:s', $this->resources_is_accessed_list[$id]['dt_to']);
        //     $SAfter = date('Y-m-d\TH:i:s', $this->resources_is_accessed_list[$id]['dt_from']);
        //     $temp = [];
        //     $temp['id'] = $sind;
        //     $temp['timezone'] = 'local';
        //     $temp['type'] = 'csdn_spb';
        //     $temp['dataset'] = [];

        //     $result = $client->__soapCall("getData",array(
        //         "user"        => "test",
        //         "pass"        => "test",
        //         "stations"    => "$sind", 
        //         "streams"     => 1,
        //         "sources"     => " ",
        //         "bseq"        => " ",				    
        //         "codes"       => "$scode", 
        //         "proc"        => " ",
        //         "periods"     => " ",
        //         "pkind"       => " ",
        //         "height"      => " ",
        //         "hashes"      => " ",
        //         "units"       => " ",
        //         "before"      => $SBefore,
        //         "after"       => $SAfter,
        //         "syn_hours"   => " ",
        //         "limit"       => " ",
        //         "min_quality" => "1",
        //         "start_id"    => " ",
        //         "nulls"       => " ",
        //         "local_time"  => "1",   // in local time ("local_time"=>" " in UTC)
        //         "verbose"     => " ",
        //         "alarm"       => 0
        //     ));

        //     usort($result ,'TU\cmp');

        //     foreach($result as $val) {
        //         $lval = $val->value;
        //         $tt = ['date_unix' => strtotime($val->meas_time), 'date' => date("d.m.Y\TH:i", strtotime($val->meas_time)), 'lvl' => $lval, 'unit' => $val->unit];
        //         $temp['dataset'][] = $tt;
        //     }

        //     $full_result['stations'] = $temp;
        // }
        // $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;

    }


    public function getIbase($ids_list){
        $db = new ibaseResDatabase();
        $conn = $db->getConnection();
        $sind = '';
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "ibase",
            "status" => 0 
        ];
        // $db_refs_list = [];
        // $db_ids_list = [];
        // ? not in one list in a case of different time access
        foreach ($ids_list as $id){
            $sind = $this->resources_is_accessed_list[$id]['source_db_id'];
            // $sref = $this->resources_is_accessed_list[$id]['source_db_ref'];
            $SBefore = date('Y-m-d H:i:s', $this->resources_is_accessed_list[$id]['dt_to']);
            $SAfter = date('Y-m-d H:i:s', $this->resources_is_accessed_list[$id]['dt_from']);
            $temp = [];
            $temp['id'] = $sind;
            $temp['timezone'] = 'MSK';
            $temp['type'] = 'ibase';
            $temp['dataset'] = [];

            $query =  "select l.sdate_time, l.lvl, l.rec_lvl
                from levels l
                where l.sobj = :sind  and l.sdate_time between :after and :before 
                order by l.sdate_time";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':sind', $sind);
            $stmt->bindParam(':after', $SAfter);
            $stmt->bindParam(':before', $SBefore);

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $lval = $row['LVL'];
                if ($lval == NULL) 
                    continue;
                $tt = ['date_unix' => strtotime($row['SDATE_TIME']), 'date' => date("d.m.Y\TH:i", strtotime($row['SDATE_TIME'])), 'temp' => '', 'lvl' => $lval, 'unit' => 'mm'];
                
                $temp['dataset'][] = $tt;            
            }

            $full_result['stations'] = $temp;
        }

        $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;

        
    }


    public function getSts($ids_list){
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "sts",
            "status" => 0 
        ];
        $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;

    }


    public function getPg($ids_list){
        $full_result = [
            "msg" => "",
            "stations" => array(),
            "type" => "pg",
            "status" => 0 
        ];
        $full_result['status'] = count($full_result['stations']) > 0 ? 1 : 0;

        return $full_result;
    }

}

$res = new ApiMeteo();
$res->getRequest();


?>