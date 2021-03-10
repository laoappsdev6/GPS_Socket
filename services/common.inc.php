<?php
session_start();
//include_once('lang.inc.php');
//include_once('../config/config.inc.php');
include_once('jwt.php');

/*
 * custom timezone convert to server timezone
 * server = time + (server - client) * 3600
 */
function toServerTime($time, $timezone) {    
    return date('Y-m-d H:i:s', $time + ($GLOBALS['SERVER_TIMEZONE'] - $timezone) * 3600);
}

function toDate($time){
    return date('Y-m-d', $time);
}
/*
 * server timezone convert to client timezone
 * sample:
 * server = +8
 * client = +7.5
 * result = time - (server - client) * 3600
 */
function toCustomTime($time, $timezone, $datetimefmt) {
    if($time != null){
        $endtime = $time->getTimestamp() - ($GLOBALS['SERVER_TIMEZONE'] - $timezone) * 3600;
        return date('Y-m-d H:i:s', $endtime);
		/*客户端自己转换时间格式
		if ($datetimefmt) {
            return date($datetimefmt, $endtime);
        } else {
            return date('Y-m-d H:i:s', $endtime);
        }
		*/
    }else{
        return '';
    }
}
function toCustFmtTime($time, $datetimefmt){
    if($time != null){
        return date('Y-m-d H:i:s', $time->getTimestamp());
		/*客户端自己转换时间格式
		if ($datetimefmt) {
            return date($datetimefmt, $time->getTimestamp());
        } else {
            return date('Y-m-d H:i:s', $time->getTimestamp());
        }
		*/
    }else{
        return '';
    }
}

function secondsToWords($seconds){
    $TEXT = $GLOBALS['TEXT'];
	$ret = "";

    /*** get the days ***/
    $days = intval(intval($seconds) / (3600*24));
    if($days> 0)
    {
        $ret .= "$days " . $TEXT['js-dhms-day'];
    }

    /*** get the hours ***/
    $hours = (intval($seconds) / 3600) % 24;
    if($hours > 0)
    {
        $ret .= " $hours " . $TEXT['js-dhms-hour'];
    }

    /*** get the minutes ***/
    $minutes = (intval($seconds) / 60) % 60;
    if($minutes > 0)
    {
        $ret .= " $minutes " . $TEXT['js-dhms-min'];
    }

    /*** get the seconds ***/
    $seconds = intval($seconds) % 60;
    if ($seconds > 0) {
        $ret .= " $seconds " . $TEXT['js-dhms-second'];
    }

    return $ret;
}

function getIonValue($io, $iotable){
	$ios = explode(',', $iotable); 
	for($i = 0; $i < count($ios); $i++){
		 if(strpos($ios[$i], $io . ":") === 0){
			 return $ios[$i] . ",";
		 }
	} 
	return '';
}

function getIoValue($io, $iotable){
	$ios = explode(',', $iotable); 
	for($i = 0; $i < count($ios); $i++){
		 if(strpos($ios[$i], $io . ":") === 0){
			 $value = explode(':', $ios[$i]);
			 return $value[1];
		 }
	} 
	return '';
}

/*
 * array to json
 */
function array2json($array) {
    return json_encode($array);;
}
/*
 * filter speed by device stauts
 */
function filterSpeed($status){
    $p1 = strpos($status, "3005");//ACC ON
    if($p1 && $p1 >= 0 && $p1 % 4 == 0){
        return 0;
    }else{
        $p2 = strpos($status, "3006");//ACC OFF
        if($p2 && $p2 >= 0 && $p2 % 4 == 0){
            return 1;
        }else{
            return 2;
        }
    }
}

function getDeviceStatus($status){
    $TEXT = $GLOBALS['TEXT'];
    for($i=0; $i<intval(strlen($status)/4); $i++){
        $state = strtoupper(substr($status, $i * 4, 4));
        $txt = $TEXT[$state];
        if(isset($txt)){
            $output[] = $txt;
        }
    }
    if(isset($output)){
        return implode (',', $output);
    }else{
        return '';
    }
}

function getDeviceIoParam($params, $ios, $online, $command){
	$timezone = isset($_SESSION['timezone']) ? (float) $_SESSION['timezone'] : 0;
	$unit_speed = $_SESSION['unit_speed'];
	$unit_dist = $_SESSION['unit_distance'];
	$unit_fuel = $_SESSION['unit_fuel'];
	$unit_temp = $_SESSION['unit_temperature'];
	
    $array = explode(',', $ios);
    if($array != null){
        foreach($array as $item){
            if($item != ''){
                $ret = explode(':', $item);
                $id = hexdec('0x'.$ret[0]);				               
				
                $func = $params[$id]['attfunc'];
				//if($func=='GEO' || $func=='MAT' || $func=='NGEO' || $func=='NPOI' || $func=='IGEO' || $func =='ENGH'){
					$value = $ret[1];
				//}else{
				//	$value = (int)$ret[1];
				//}
				
                if($func=='DIV10'){
                    $value = $value / 10;
                }
				
				/*里程单位转换*/
				if($unit_dist == 1 && ($id == 10 || $id == 63)){
					//Mile(英里)
					$value = $value * 0.6213712;
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . 'mi';
					}
				}else if($unit_dist == 2 && ($id == 10 || $id == 63)){
					//Nautical mile(海里)
					$value = $value * 0.5399568;
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . 'nmi';
					}
				}else if($unit_dist == 0 && ($id == 10 || $id == 63)){
					//Kilometer(公里)
					$params[$id]['vformat'] = $params[$id]['vformat'] . 'km';
				}
				
				if($unit_dist == 1 && ($id == 14 || $id == 15)){
					//Mile(英里)
					$dist_index = strripos($value, " ") + 1;
					$dist_len = strlen(substr($value,$dist_index)) - 2;
					$dist = round((float)substr($value,$dist_index,$dist_len) * 0.6213712,1);
					$value = substr($value,0,$dist_index) .'('. $dist . ' mi)';
				}else if($unit_dist == 2 && ($id == 14 || $id == 15)){
					//Nautical mile(海里)
					$dist_index = strripos($value, " ") + 1;
					$dist_len = strlen(substr($value,$dist_index)) - 2;
					$dist = round((float)substr($value,$dist_index,$dist_len) * 0.5399568,1);
					$value = substr($value,0,$dist_index) .'('. $dist . ' nmi)';
				}else if($unit_dist == 0 && ($id == 14 || $id == 15)){
					//Kilometer(公里)
					$dist_index = strripos($value, " ") + 1;
					$dist_len = strlen(substr($value,$dist_index)) - 2;
					$dist = round((float)substr($value,$dist_index,$dist_len),1);
					$value = substr($value,0,$dist_index) .'('. $dist . ' km)';
				}
				
				if($unit_speed == 1 && $id == 60){
					//mph(英里/小时)
					$value = round($value * 0.6213712,0);
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . ' mph';
					}
				}else if($unit_speed == 0 && $id == 60){
					$value = $value;
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . ' kph';
					}
				}
				
				/*油量单位转换*/
				if($unit_fuel == 1 && ($id == 30 || $id == 31)){
					//Gallon(加仑)
					$value = round($value * 0.2199692);
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . 'gal';
					}
				}else if($unit_fuel == 0 && ($id == 30 || $id == 31)){
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . 'L';
					}
				}
				
				/*温度单位转换*/
				if($unit_temp == 1 && ($id == 72 || $id == 73)){
					//Fahrenheit
					$value =$value * 1.8 + 32;
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . '℉';
					}
				}else if($unit_temp == 0 && ($id == 72 || $id == 73)){
					if($params[$id]['vformat'] != ''){
						$params[$id]['vformat'] = $params[$id]['vformat'] . '℃';
					}
				}
								
                $attrib = $params[$id]['attrib'] . ': ';
                if($params[$id]['vformat'] != ''){
                    $output[] = $attrib . sprintf($params[$id]['vformat'], $value);
                }else if($func=='DHMS' /*&& $online == 1*/){
					$output[] = $attrib . secondsToWords((int)$value);
				}else if($func=='LENG'){
					$output[] = $attrib . toCustomTime(new DateTime(date('Y-m-d H:i:s', (int)$value)), $timezone, $_SESSION['datetime_fmt']);
				}else if($func=='CMID'){
					$output[] = $attrib . $command[(int)$value];
				}
				else if($params[$id]['voption'] != ''){
                    $subs = explode(';', $params[$id]['voption']);
                    foreach ($subs as $item){
                        $ret = explode('=', $item);
                        if($value == (int)$ret[0]){
                            $output[] = $attrib . $value . '('. $ret[1] .')';
                            break;
                        }else{
                            $vls = explode('..', $ret[0]);
                            if(count($vls) == 2 && $value >= (int)$vls[0] && $value <= (int)$vls[1]){
                                $output[] = $attrib . $value . '('. $ret[1] .')';
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    if(isset($output)){
        return implode ("<br> ", $output);
    }else{
        return '';
    }
}

function getInfoByJson($ioparams, $data){
    $lastgid = 0;
    $ncnt = -1;
    $timezone = isset($_SESSION['timezone']) ? (float) $_SESSION['timezone'] : 0;
	$unit_speed = $_SESSION['unit_speed'];
    foreach ($data as $row) {
        if ($row != null) {
            $gid = $row['gid'];
            $row['t'] = toCustomTime($row['t'], $timezone, $_SESSION['datetime_fmt']);
			$row['ts'] = toCustomTime($row['ts'], $timezone, $_SESSION['datetime_fmt']);
			$row['ex'] = toCustomTime($row['ex'], $timezone, $_SESSION['datetime_fmt']);
            $row['in_time'] = toCustomTime($row['in_time'], $timezone, $_SESSION['datetime_fmt']);// ADD BY TOUYARA
			//speed unit
			if($unit_speed == 1 && $row['s'] >= 0){
				//mph(英里/小时)
				$row['s'] = round($row['s'] * 0.6213712,0);
			}
			
            $status = $row['e'];           
			if($status != ''){
                $row['e'] = getDeviceStatus($status) . ";";               
				/*
				if($row['on'] == 1){
                    //0=ACC ON; 1=ACC OFF; 2=OTHER
                    $over = filterSpeed($status);
                    if($over > 0){
                        if($over == 1){
                            $row['s'] = 0;
                        }else{
                            $row['s'] = $row['s'] > 2 ? $row['s'] : 0;
                        }
                    }
                }else{
                    if($row['s'] >= 0){
                        $row['s'] = 0;
                    }
                }*/
            }
            if($row['q'] != ''){
                $pid = $row['pid'];
                $row['e'] = $row['e'] . getDeviceIoParam($ioparams[$pid], $row['q'], $row['on'], $ioparams['command']);
            }
            unset($row['q']);
            if ($lastgid != $gid) {
                $ncnt++;
                $lastgid = $gid;
                $gtxt = $row['gtxt'];
                $in_time=$row['in_time'];
                //array_splice($row, 0, 3);   // CHANGED BY TOUYARA             
                $result[$ncnt] = array('gid' => $gid, 'gtxt' => $gtxt, 'item' => array($row));
            } else {
                //array_splice($row, 0, 3); // CHANGED BY TOUYARA
                array_push($result[$ncnt]['item'], $row);
            }
        }
    }
    $json = array2json($result);
    return $json;
}
?>
