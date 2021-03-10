<?php
require_once __DIR__."./../config/config.inc.php";
require_once __DIR__."./../services/common.inc.php";
require_once __DIR__.'./db.class.php';
require_once __DIR__.'./db.sqlsrv.php';

class StateCheck {

    public function createOnline() {
        $online = array('last_stamp' => time() - 6, 'list' => array());
        return $online;
    }
//dbo.fn_to_client_time(dateadd(day, -1, getdate()), z.time_zone) and dbo.fn_to_client_time(getdate(), z.time_zone)
    public function createEndPoint($user_id) {
		$endpoint=[];
		$offline_timeout = $GLOBALS['GLOBAL_DEVICE_OFFLINE_TIMEOUT'];
		
        $db = new db_mssql();// ADD BY TOUYARA z.device_no z.device_sim d.device_sim z.install_time in_time, z.last_stamp ex
        $sql = "select z.device_no d_no,z.device_sim d_sim,z.install_time in_time,z.last_stamp ex, z.object_id n, z.object_flag c, ds.gps_time t, 
					case when datediff(ss,ds.rcv_time,getdate()) < $offline_timeout then 1
					when z.online = 1 then 1
					else 0 end [on], isnull(ds.valid,0) v, count(a.alarm_time) a,
					z.group_id gid, z.group_name gtxt, z.object_kind i
				from (
					select d.device_sim,d.install_time,d.last_stamp, o.object_id, o.object_flag, o.group_id, g.group_name, o.object_kind, o.time_zone, d.online, d.device_no, t.can_alarm
					from cfg_device d, cfg_object o, cfg_group g, sys_device_type t
					where d.object_id = o.object_id and o.group_id = g.group_id and d.dtype_id = t.dtype_id
					and o.group_id in
					(
						select group_id from dbo.fn_group4user($user_id)
					)
				) z 
				left join cfg_device_state ds on ds.device_no = z.device_no
				left join dat_alarm a on z.can_alarm = 1 and z.device_no = a.device_no 
					and a.alarm_time between dateadd(hour, -1, getdate()) and getdate()
					and a.finish_time is null
				group by z.object_id, z.object_flag, z.online, ds.gps_time, ds.rcv_time, ds.valid, z.group_id, z.group_name, z.object_kind, z.device_no,z.device_sim,z.install_time,z.last_stamp
				order by gtxt, c";
        $data = $db->query($sql);

        if (!empty($data)) {
            foreach ($data as $row) {
                if ($row) {
                    $objid = trim($row['n']);
                    $endpoint[$objid] = array(
                        'c' => $row['c'],
						't' => $row['t'],
						'ts' => $row['ts'],
                        'on' => $row['on'],                        
                        'v' => $row['v'],
                        'a' => $row['a'],
                        'gid' => $row['gid'],
                        'gtxt' => $row['gtxt'],
						'i' => $row['i'],
						'ex'=> $row['ex'],
						'in_time' => $row['in_time'],
						'd_no' => $row['d_no'],
						'd_sim' => $row['d_sim'],
                        );
                }
            }
        }
        return $endpoint;
    }

    public function timeCheck(&$online, &$deviceinfo) {
		/*if user disable or password change*/
		$db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
		$uid = $_SESSION['uid'];
		$pass = $_SESSION['pass'];
		$sql = "select user_id from sys_user where user_id = $uid and login_pass = '$pass' and valid = 1";
		$user_data = $db->query($sql); 
		if (empty($user_data) ) {
		   session_unset();
		   return;
		}
		
        $changed = false;
        if (($online['last_stamp'] + 5) <= time()) {          
            $online['last_stamp'] = time();
			$offline_timeout = $GLOBALS['GLOBAL_DEVICE_OFFLINE_TIMEOUT']; // ADD BY TOUYARA z.device_no z.device_sim d.device_sim z.install_time itime, 
            $sql = "select z.device_no d_no,z.device_sim d_sim,z.install_time in_time, z.group_id gid, z.group_name gtxt, z.protocol_id pid, z.object_id n, ds.gps_time t, ds.rcv_time ts,
					case when datediff(ss,ds.rcv_time,getdate()) < $offline_timeout then 1
					when z.online = 1 then 1
					else 0 end [on], z.object_kind i, z.object_flag c, 
					isnull(ds.lng, 0) x, isnull(ds.lat, 0) y, isnull(ds.angle, 0) d, 
					case when datediff(ss, z.ex, getdate()) > 0 then -1
					when ds.gps_time is null then 0
					when ds.rcv_time is null then 0
					else round(ds.speed/1,0)
					end s, isnull(ds.valid, 0) v,z.dtype_id dt, isnull(ds.sta_table,'') e, isnull(ds.sta_table,'') st, isnull(ds.ios_table, '') q,isnull(ds.ios_table, '') io,
					count(a.alarm_time) a, isnull(dr.job_number,'') jb, isnull(dr.driver_name,'') dn ,z.ex
					--s.mil_maintenance_enable mile,s.mil_maintenance_value milv,s.mil_maintenance_name miln,s.mil_maintenance_last mill,
					--s.eng_maintenance_enable enge,s.eng_maintenance_value engv,s.eng_maintenance_name engn,s.eng_maintenance_last engl,
					--s.day_maintenance_enable daye,s.day_maintenance_value dayv,s.day_maintenance_name dayn,s.day_maintenance_last dayl
				from (
					select d.device_no,d.device_sim,d.install_time, o.object_id, d.dtype_id,  o.group_id, g.group_name, t.protocol_id,
					d.online, d.last_stamp ex, o.object_kind, o.object_flag, o.userdef_flag, o.time_zone, t.can_alarm
					from cfg_device d, cfg_object o, cfg_group g, sys_device_type t
					where d.object_id = o.object_id and o.group_id = g.group_id
						and d.dtype_id = t.dtype_id
				) z
				left join cfg_device_state ds on ds.device_no = z.device_no
				left join dat_alarm a on z.can_alarm = 1 and z.device_no = a.device_no 
					and a.alarm_time between dateadd(hour, -1, getdate()) and getdate()
					and a.finish_time is null
				left join dat_rfid_last rl on ds.device_no = rl.device_no
				left join cfg_driver dr on rl.rfid = dr.rfid
				left join  cfg_services s on z.object_id = s.object_id
				group by z.group_id, z.group_name, z.protocol_id, z.object_id, 
					z.online, z.object_kind, z.object_flag, z.ex,z.dtype_id,
					ds.gps_time, ds.rcv_time, ds.lng, ds.lat, ds.angle, ds.speed, ds.valid, ds.sta_table, ds.ios_table, dr.job_number, dr.driver_name, z.device_no,z.device_sim,z.install_time
					--s.mil_maintenance_enable,s.mil_maintenance_value,s.mil_maintenance_name,s.mil_maintenance_last,
					--s.eng_maintenance_enable,s.eng_maintenance_value,s.eng_maintenance_name,s.eng_maintenance_last,
					--s.day_maintenance_enable,s.day_maintenance_value,s.day_maintenance_name,s.day_maintenance_last
				order by gtxt, c";
            $data = $db->query($sql);
            if (!empty($data)) {
                $deviceinfo = array();
                foreach ($data as $row) {
                    if ($row != null) {
                        $objid = trim($row['n']);
						//Expired
						if($row['s'] < 0){
							$row['x'] = 0;
							$row['y'] = 0;
							$row['on'] = 0;
							$row['v'] = 0;
							$row['e'] = '';
							$row['q'] = '';
							$row['a'] = 0;
						}
                        $deviceinfo[$objid] = $row;
                    }
                }
                $changed = true;
            }
        }
        return $changed;
    }

    public function getData($deviceinfo, &$endpoint, $first, $object = null, $start ="") {
		if (isset($object)) {
            $data[] = $deviceinfo[$object];
        } else {
            if($endpoint != null && count($endpoint) > 0){
				if($start >= 0){
					$endpoint_part = array_slice($endpoint, $start, $GLOBALS['GLOBAL_LOAD'], true);
					foreach ($endpoint_part as $objid => $info) {
						$data[] = $deviceinfo[$objid];
						$row = $deviceinfo[$objid];
						$data[] = $row;
						
						$endpoint[$objid]['c'] = $row['c'];
						$endpoint[$objid]['t'] = $row['t'];
						$endpoint[$objid]['ts'] = $row['ts'];
						$endpoint[$objid]['on'] = $row['on'];
						$endpoint[$objid]['v'] = $row['v'];
						$endpoint[$objid]['a'] = $row['a'];
						$endpoint[$objid]['gid'] = $row['gid'];
						$endpoint[$objid]['gtxt'] = $row['gtxt'];
						$endpoint[$objid]['i'] = $row['i'];
						$endpoint[$objid]['jb'] = $row['jb'];
						$endpoint[$objid]['ex'] = $row['ex'];
					}
				}else{
					foreach ($endpoint as $objid => $info) {
						$row = $deviceinfo[$objid];
						$changed = false;
						try {
							if ($info['c'] != $row['c'] or $row['t'] > $info['t'] or $row['ts'] > $info['ts']
								or $info['on'] != $row['on'] or $row['v'] != $info['v'] 
								or $row['a'] != $info['a'] or $row['gid'] != $info['gid']
								or $row['gtxt'] != $info['gtxt'] or $row['i'] != $info['i'] or $row['jb'] != $info['jb'] or $row['ex'] != $info['ex'])
							{
								$endpoint[$objid]['c'] = $row['c'];
								$endpoint[$objid]['t'] = $row['t'];
								$endpoint[$objid]['ts'] = $row['ts'];
								$endpoint[$objid]['on'] = $row['on'];
								$endpoint[$objid]['v'] = $row['v'];
								$endpoint[$objid]['a'] = $row['a'];
								$endpoint[$objid]['gid'] = $row['gid'];
								$endpoint[$objid]['gtxt'] = $row['gtxt'];
								$endpoint[$objid]['i'] = $row['i'];
								$endpoint[$objid]['jb'] = $row['jb'];
								$endpoint[$objid]['ex'] = $row['ex'];
								$endpoint[$objid]['in_time'] = $row['in_time'];
								$endpoint[$objid]['d_sim'] = $row['d_sim'];
								$endpoint[$objid]['d_no'] = $row['d_no'];
								$changed = true;
							}
							if ($first or $changed) {
								$data[] = $row;
							}
						} catch (Exception $e) {

						}
					}
				} 
            }
        }
		return $data;
    }

    public function queryHistory($object, $time1, $time2) {
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
		$sql_query_device_id = "select dbo.fn_track4device_no(dbo.fn_device4oid($object)) as table_name";
		
		$data_device_id = $db->query($sql_query_device_id);
		if (!empty($data_device_id)) {
			$track_table_name = $data_device_id[0]['table_name'];
			$max_points = $GLOBALS['GLOBAL_DOWNLOAD_MAX_POINTS'];
			
			$sql = "declare @device_no nvarchar(20) = dbo.fn_device4oid($object),
					        @last_stamp datetime,
							@total int
							
					select @last_stamp = last_stamp from cfg_device where device_no = @device_no
					  
					if datediff(ss, @last_stamp, getdate()) <= 0
					begin
						select @total = count(*) from ".$track_table_name." 
						where gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20)
						
						if @total <= $max_points
						begin
							select distinct x, y, h, s, d, v, tg, ts, e, q, f from (
							select distinct lng x, lat y, round(speed/1,0) s, high h, angle d, valid v, gps_time tg, rcv_time ts, sta_table e, ios_table q, isnull(dbo.fn_io4value('1e',ios_table),0) f 
							from ".$track_table_name." h
							where (lat <> 0 and lng <> 0)
							and gps_time >= convert(datetime, '$time1', 20) and gps_time < convert(datetime, '$time2', 20) 			
							) tg
							order by tg 							
						end
					end";									  			
			try{
				$data = $db->query($sql);
				return $data;
			}catch(Exception $e){
				return $e->getMessage();
			}
		}else{
			echo 'error';
		}
    }
	
	public function queryEvent($object, $time1, $time2) {
        $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
		$sql = "select o.object_flag c, a.alarm_id n, a.alarm_type a, a.gps_time t, a.lng x, a.lat y, round(a.speed/1,0) s, a.angle d, a.sta_table e, a.ios_table q, dt.protocol_id pid
					  from cfg_device d, cfg_object o, sys_device_type dt, dat_alarm a
					  where d.object_id = o.object_id and dt.dtype_id = d.dtype_id
					  and a.device_no = d.device_no and o.object_id = $object
					  and a.gps_time >= convert(datetime, '$time1', 20) and a.gps_time < convert(datetime, '$time2', 20)
					  and a.alarm_type <> 20482					  
					  ";								
		try{				
			$data = $db->query($sql);
			return $data;
		}catch(Exception $e){
			return $e->getMessage();
		}
    }
    
    public function queryIoParams($lang, &$ioparams){
        if($ioparams[$lang] == null || ($ioparams['last_stamp'] + 600) <= time()) {
            $ioparams['last_stamp'] = time();
            $db = new db_mssql($GLOBALS['db_host'], $GLOBALS['db_dbms'], $GLOBALS['db_user'], $GLOBALS['db_pass']);           
			
			$sql = "select protocol_id pid, element_id id, dbo.fn_trans_entry('$lang', attrib_name) attrib, 
                dbo.fn_trans_entry('$lang', value_format) vformat,
                dbo.fn_trans_entry('$lang', value_option) voption, attach_func attfunc
                from sys_io_element where valid = 1";
            $data = $db->query($sql);
            foreach($data as $item){
                $pid = $item['pid'];
                $id = $item['id'];
                $attrib = $item['attrib'];
                $format = $item['vformat'];
                $option = $item['voption'];
                $atfunc = $item['attfunc'];
                $ioparams[$lang][$pid][$id] = array('attrib'=>$attrib, 'vformat' => $format, 'voption'=>$option, 'attfunc'=>$atfunc);
            }
			
			$sql = "select command_id cid, dbo.fn_trans_entry('$lang', command_name) cname from sys_command";
			$cmds = $db->query($sql);
			foreach($cmds as $item){
				$cid = $item['cid'];
				$cname = $item['cname'];
				$ioparams[$lang]['command'][$cid] = $cname;
			}
			
            return true;
        } else
            return false;
    }


}