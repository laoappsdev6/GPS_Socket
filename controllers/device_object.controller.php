<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";

class DeviceObjectController
{
    public $socket;
    public $token;
    public $command;
    public function __construct()
    {
        $this->socket = $_SESSION["socket"];
        $this->token = $_SESSION["token"];
        $this->command = $_SESSION["command"];
    }
    public function addDeviceObject($dv)
    {
        try {
            date_default_timezone_set("Asia/Bangkok");
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $regex = '/^(\+|\-)(\d{2})\:(\d{2})$/'; // 10:00
            $matches = array();
            $ztimes = 'null';
            if (preg_match($regex, $dv->ztime, $matches)) {
                if (count($matches) == 4) {
                    $ztimes = (int) $matches[2] * 60 + (int) $matches[3];
                    if ($matches[1] == "-") {
                        $ztimes = -$ztimes;
                    }
                }
            }
            $install = $dv->stamp == "-1" ? date('Y-m-d H:i:s', time()) : $dv->stamp;
            $expired = $dv->estamp == "-1" ? date('Y-m-d H:i:s', strtotime("+1 year")) : $dv->estamp;
            $subsql = "
					declare @purview    int
					exec @purview = dbo.p_user_have_purview $user_id, 1090, 'A'
					if @purview > 0
					begin
						set @code = -1
						declare @olimit int
						select @olimit = limit_object from sys_user where user_id = $user_id;
						if @olimit > 0 or $user_id = 1
						begin
							insert into cfg_device (object_id, dtype_id, device_state, device_no, device_sim, device_pass, install_time, install_addr, last_stamp)
							values (0, $dv->dtype, $dv->dstate, '$dv->devno', '$dv->simno', '$dv->dpass', '$install', '$dv->iaddr', '$expired' )
							set @code = -2
							insert into cfg_object (customer_id, group_id, object_kind, object_flag, userdef_flag, time_zone, driver_job_number, remark)
							values ($dv->cinfo, $dv->ginfo, $dv->okind, N'$dv->oflag', N'$dv->uflag', $ztimes, N'$dv->driver', N'$dv->remark')
							set @objid = @@identity
							set @code = -3
							update cfg_device set object_id = o.object_id from cfg_object o
							where device_no = '$dv->devno' and o.object_flag = N'$dv->oflag'
							if $user_id <> 1
								update sys_user set limit_object = (@olimit -1) where user_id = $user_id;
							set @code = -4

							if '$dv->driver' = '-1'
							    delete from dat_rfid_last where device_no = '$dv->devno'
							else
								begin
									if not exists(select * from dbo.dat_rfid_last where device_no = '$dv->devno')
									begin
										insert into dbo.dat_rfid_last
											(device_no, rfid)
										values
											('$dv->devno', (select rfid from cfg_driver where job_number = '$dv->driver'))
									end
									else
										update dbo.dat_rfid_last
										set rfid = (select rfid from cfg_driver where job_number = '$dv->driver')
										where device_no = '$dv->devno'
								end
							set @code = 0
						end
						else
							set @code = -5
                    end
					else
						set @code = -20";
            // echo $subsql;die();

            $sql = "declare @code int, @objid int
						begin try
							begin tran
							$subsql
							commit tran
						end try
						begin catch
							rollback tran
						end catch

						select @code as errcode, @objid as objectid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            $object_id = $data[0]['objectid'];
            /** TOUYARA* CREATE DEFAULT SERVICES FOR NEW ADDED OBJECT 25MAR2020*/
            if ($error_code == 0) {
                $q = "insert  [ANB-GPSDB].[dbo].[CFG_SERVICES] ([OBJECT_ID])
					SELECT DISTINCT CB.OBJECT_ID
					FROM [ANB-GPSDB].[dbo].[CFG_OBJECT] CB
					WHERE NOT EXISTS (SELECT * FROM  [ANB-GPSDB].[dbo].[CFG_SERVICES] C
								  WHERE CB.[OBJECT_ID] = C.[OBJECT_ID])";
                $db->query($q);
                $q = "update  [ANB-GPSDB].[dbo].[CFG_SERVICES] SET
					[SPEED_ALARM] = 90
					,[IDLE_ALARM] = 3
					,[TRACKER_LOW_BAT]=50
					,[CAR_LOW_BAT]=9
					,[FATIGUE_DRIVING]=6
					,[OBD_MILEAGE]=0
					,[OBD_MILEAGE_BY]=1
					,[ENGINE_HOUR]=0
					,[ENGINE_HOUR_BY]=2
					,[MIN_MOVING_SPEED]=2
					,[MIN_IDLE_SPEED]=5
					,[DETECT_STOP_USING]=1
					,[FUEL_TANK_CAPACITY]=55
					,[FUEL_UPLOAD_TYPE]=1
					/*,[STATE_EVENT_TABLE]= '12293,12294,12313,12314,8197'*/
					 ,[STATE_EVENT_TABLE]= '12293,12294,12313,12314,8197,4170,8208,8209,20482,4171,4173,12299'
					,[FUEL_100_KM]=9
					,[OBJECT_VOLTAGE]=0
					,[ALLOW_TEMP_FROM]=0
					,[ALLOW_TEMP_TO]=0
					,[ENABLE_NOTIFICATION]=0
					,[MIL_MAINTENANCE_ENABLE] =0
					,[MIL_MAINTENANCE_VALUE] = 0
					,[MIL_MAINTENANCE_LAST] =0
					,[ENG_MAINTENANCE_ENABLE]=0
					,[ENG_MAINTENANCE_VALUE]=0
					,[ENG_MAINTENANCE_LAST]=0
					,[DAY_MAINTENANCE_ENABLE]=0
					,[DAY_MAINTENANCE_VALUE]=0
					,[DAY_MAINTENANCE_LAST] ='1900-01-01 00:00:00.000'
				  ";
                $db->query($q);

                $json = array("objid" => "$object_id", "message" => "add device and object Ok", "status" => "1");
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "add device and object ok", 1, "", "");
                } else {
                    echo json_encode($json);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add data fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "add data fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "add data fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "add data fail error: " . $e, 0);
            }
        }
    }
    public function udpateDeviceObject($dv)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone_ = (float) $_SESSION['timezone'];
            $regex = '/^(\+|\-)(\d{2})\:(\d{2})$/';
            $matches = array();
            $ztimes = 'null';
            if (preg_match($regex, $dv->ztime, $matches)) {
                if (count($matches) == 4) {
                    $ztimes = (int) $matches[2] * 60 + (int) $matches[3];
                    if ($matches[1] == "-") {
                        $ztimes = -$ztimes;
                    }
                }
            }

            $install = toServerTime(strtotime($dv->stamp), $time_zone_);
            $expired = toServerTime(strtotime($dv->estamp), $time_zone_);

            // print_r($install);die();
            $subsql = "
            declare @purview    int,
                    @have       int,
                    @expired    int
            exec @purview = dbo.p_user_have_purview $user_id, 1090, 'M'
            exec @have = dbo.p_user_have_object $user_id, $dv->objid
            exec @expired = dbo.p_user_have_purview $user_id, 1090, 'E'

            if @purview > 0 and @have > 0 and @expired > 0
            begin
                set @code = -1
                set @objid = $dv->objid
                update cfg_device set device_no = '$dv->devno', dtype_id = $dv->dtype, device_state = $dv->dstate, device_sim = '$dv->simno', device_pass = '$dv->dpass', install_addr = N'$dv->iaddr', install_time = '$install', last_stamp ='$expired'
                where object_id = $dv->objid
                set @code = -2
                update cfg_object set customer_id = $dv->cinfo, group_id = $dv->ginfo, object_kind = $dv->okind, object_flag = N'$dv->oflag', userdef_flag = N'$dv->uflag',  time_zone = $ztimes, driver_job_number = N'$dv->driver', remark = N'$dv->remark'
                where object_id = $dv->objid

                if '$dv->driver' = '-1'
                    delete from dat_rfid_last where device_no = '$dv->devno'
                else
                    begin
                        if not exists(select * from dbo.dat_rfid_last where device_no = '$dv->devno')
                        begin
                            insert into dbo.dat_rfid_last
                                (device_no, rfid)
                            values
                                ('$dv->devno', (select rfid from cfg_driver where job_number = '$dv->driver'))
                        end
                        else
                            update dbo.dat_rfid_last
                            set rfid = (select rfid from cfg_driver where job_number = '$dv->driver')
                            where device_no = '$dv->devno'
                    end
                set @code = 0
            end
            else
                if @purview > 0 and @have > 0 and @expired = 0
                begin
                    set @code = -1
                    set @objid = $dv->objid
                    update cfg_device set device_no = '$dv->devno', dtype_id = $dv->dtype, device_state = $dv->dstate, device_sim = '$dv->simno', device_pass = '$dv->dpass', install_addr = N'$dv->iaddr'
                    where object_id = $dv->objid
                    set @code = -2
                    update cfg_object set customer_id = $dv->cinfo, group_id = $dv->ginfo, object_kind = $dv->okind, object_flag = N'$dv->oflag', userdef_flag = N'$dv->uflag',  time_zone = $ztimes, driver_job_number = N'$dv->driver', remark = N'$dv->remark'
                    where object_id = $dv->objid

                    if '$dv->driver' = '-1'
                        delete from dat_rfid_last where device_no = '$dv->devno'
                    else
                    begin
                        if not exists(select * from dbo.dat_rfid_last where device_no = '$dv->devno')
                        begin
                            insert into dbo.dat_rfid_last
                                (device_no, rfid)
                            values
                                ('$dv->devno', (select rfid from cfg_driver where job_number = '$dv->driver'))
                        end
                        else
                            update dbo.dat_rfid_last
                            set rfid = (select rfid from cfg_driver where job_number = '$dv->driver')
                            where device_no = '$dv->devno'
                    end

                    set @code = 0
                end
                else
                    set @code = -20";

            //   echo $subsql;die();

            $sql = "declare @code int, @objid int
                begin try
                    begin tran
                    $subsql
                    commit tran
                end try
                begin catch
                    rollback tran
                end catch

                select @code as errcode, @objid as objectid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if ($error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update data ok", 1, "", "");
                } else {
                    PrintJSON("", "update data ok", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "update fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "update fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "update fail error: " . $e, 0);
            }
        }
    }
    public function deleteObject($objid)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];

            $subsql = "
                        declare @purview    int,
                                @have       int
                        exec @purview = dbo.p_user_have_purview $user_id, 1090, 'D'
                        exec @have = dbo.p_user_have_object $user_id, $objid
                        if @purview > 0 and @have > 0
                        begin
                            set @code = -1
                            set @objid = $objid
                            declare @device_no nvarchar(20)
                            select @gid = group_id from cfg_object where object_id = $objid
                            select @device_no = device_no from cfg_device where object_id = $objid;
                            set @code = -2
                            delete from cfg_device where device_no = @device_no;
                            set @code = -3
                            delete from cfg_services where object_id = $objid;
                            set @code = 0
                        end
                        else
                            set @code = -20
                    ";
            // echo $subsql();die();
            $sql = "declare @code int, @objid int, @gid int
                            begin try
                                begin tran
                                $subsql
                                commit tran
                            end try
                            begin catch
                                rollback tran
                            end catch

                        select @code as errcode";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if ($error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "ID: " . $objid . "  delete Ok", 1, "", "");
                } else {
                    PrintJSON("", "ID: " . $objid . "  delete Ok", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "delete fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "delete fail error: " . $error_code, 0);
                }
            }

        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "delete fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "delete fail error: " . $e, 0);
            }
        }
    }
    public function Objectlist($dv)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            if (isset($dv->objid)) {
                //query one

                $time_zone = (float) $_SESSION['timezone'];
                $sql = "select d.dtype_id dtype, d.device_no devno, d.device_state dstate, d.device_sim simno, d.device_pass dpass,
                dbo.fn_sec2time(60 * isnull(time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, d.install_addr iaddr, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp,
                o.object_id objid, o.group_id ginfo, o.customer_id cinfo, o.object_flag oflag, o.driver_job_number jb,
                o.userdef_flag uflag, o.object_kind okind, o.remark remark, dbo.fn_group1user(o.group_id, $user_id) valid
                    from cfg_device d, cfg_object o, sys_device_type dt
                    where d.object_id = o.object_id and dt.dtype_id = d.dtype_id
                     and o.object_id = $dv->objid";
                $data = $db->query($sql);
                $json = array2json($data);
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list one of device and object", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $sql = "select customer_id id, short_name name from dbo.cfg_customer where customer_id in
                       (select customer_id from dbo.cfg_object where group_id in ( select group_id from dbo.fn_group4user($user_id)))
                       union
                       select customer_id id, short_name name from dbo.cfg_customer where user_id = $user_id
                       union
                       select customer_id id, short_name name from dbo.cfg_customer where user_id in (select user_id from dbo.fn_user_tree($user_id))
                       ";
                $custs = $db->query($sql);

                $sql = "select dtype_id id, dtype_name name from sys_device_type order by dtype_name";
                $dtype = $db->query($sql);

                $sql = "select job_number jb, driver_name jn from dbo.cfg_driver d where d.job_number in
                        (select driver_job_number from dbo.cfg_object where group_id in ( select group_id from dbo.fn_group4user($user_id)))
                        union
                        select job_number jb, driver_name jn from dbo.cfg_driver d where d.user_id = $user_id
                        union
                        select job_number jb, driver_name jn from dbo.cfg_driver d where d.user_id in (select user_id from dbo.fn_user_tree($user_id))";
                $drivers = $db->query($sql);

                $sql = "select b.group_id id,
                        case when cc>1 then '['+ ltrim(str(b.group_id)) +'] ' +b.group_name else b.group_name end name, b.group_parent parent from (
                        select group_name, count(*) cc from cfg_group
                        where group_id in (
                            select group_id from dbo.fn_group4user($user_id)
                        )
                        group by group_name
                    ) a,(
                        select group_id,group_name,group_parent from cfg_group g1
                        where g1.group_id in (
                            select group_id from dbo.fn_group4user($user_id)
                        )
                    ) b
                    where a.group_name = b.group_name
                    order by b.group_name, b.group_id";
                $group = $db->query($sql);

                $lang = $_SESSION['lang'];
                $time_zone = (float) $_SESSION['timezone'];
                $sql = "select kind_id id, dbo.fn_trans_entry(upper('$lang'), kind_name) name from sys_object_kind order by kind_id";
                $okind = $db->query($sql);

                $sql_pur = "select purview_id pid, isnull(purview,'') p from cfg_user_purview where user_id = $user_id and purview_id = 1090";
                $upurview = $db->query($sql_pur);

                //query all
                $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number jb, d.dtype_id dtype, d.device_state dstate, o.group_id ginfo,d.device_no devno,d.device_sim p,
                    dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                    convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                from cfg_user_purview p,cfg_device d, cfg_object o, cfg_group g
                where p.user_id = $user_id and p.purview_id = 1000 and d.object_id = o.object_id and o.group_id = g.group_id
                    and o.group_id in (select * from dbo.fn_group4user($user_id))
                order by g.group_name, g.group_id, o.object_flag";
                $olist = $db->query($sql);
                $jc = array2json($custs);
                $jt = array2json($dtype);
                $jg = array2json($group);
                $jk = array2json($okind);
                $jo = array2json($olist);
                $pr = array2json($upurview);
                $dlist = array2json($drivers);
                $json = "{\"cust\": $jc, \"type\": $jt, \"group\": $jg, \"kind\": $jk, \"list\": $jo, \"pur\": $pr, \"dlist\": $dlist}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list all of device and object", 1, "", "");
                } else {
                    echo $json;
                }
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function ObjectListPage($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            if ($cat->page == "" && $cat->limit == "") {
                $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number driver, d.dtype_id dtype,dt.dtype_name, d.device_state dstate, o.group_id ginfo,g.group_name,d.device_no devno,d.device_sim p,
                    d.device_pass dpass,d.install_addr iaddr,o.customer_id cinfo,o.object_kind okind,o.userdef_flag uflag,o.remark,
                    dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                    convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp,
                    (select driver_name from cfg_driver as dv where o.driver_job_number = dv.job_number) as driver_name
                from cfg_user_purview p,cfg_device d, cfg_object o, cfg_group g,sys_device_type  dt
                where p.user_id = $user_id and p.purview_id = 1000 and d.object_id = o.object_id and o.group_id = g.group_id and d.dtype_id=dt.dtype_id
                    and o.group_id in (select * from dbo.fn_group4user($user_id))  order by o.object_id desc ";
                $doquery = $db->query($sql);
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list all of device and object", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $offset = (($cat->page - 1) * $cat->limit);

                $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number driver, d.dtype_id dtype,dt.dtype_name, d.device_state dstate,d.online, o.group_id ginfo,g.group_name,d.device_no devno,d.device_sim p,
                d.device_pass dpass,d.install_addr iaddr,o.customer_id cinfo,o.object_kind okind,o.userdef_flag uflag,o.remark,
                dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp,
                (select driver_name from cfg_driver as dv where o.driver_job_number = dv.job_number) as driver_name
                from cfg_user_purview p,cfg_device d, cfg_object o, cfg_group g,sys_device_type  dt
            where p.user_id = $user_id and p.purview_id = 1000 and d.object_id = o.object_id and o.group_id = g.group_id and d.dtype_id=dt.dtype_id
                and o.group_id in (select * from dbo.fn_group4user($user_id)) ";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' or
                                     g.group_name like '%$cat->keyword%' or
                                     dt.dtype_name like '%$cat->keyword%' or
                                     o.object_id like '%$cat->keyword%'  ) ";
                }
                $sql_page = "order by o.object_id desc offset $offset rows fetch next $cat->limit rows only  ";
                // echo $sql;die();
                $doquery = $db->query($sql);

                if ($doquery > 0) {
                    $count = sizeof($doquery);
                    if ($count > 0) {
                        $data = $db->query($sql . $sql_page);
                        $list1 = json_encode($data);
                    }
                } else {
                    $list1 = json_encode([]);
                    $count = 0;
                }

                $number_count = $count;
                $total_page = ceil($number_count / $cat->limit);
                $list3 = json_encode($total_page);
                $json = "{  \"Data\":$list1,
                            \"Page\":$cat->page,
                            \"Pagetotal\":$list3,
                            \"Datatotal\":$number_count
                        }";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list page of device and object", 1, "", "");
                } else {
                    echo $json;
                }
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function ObjectListby_imei($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number driver, d.dtype_id dtype,dt.dtype_name, d.device_state dstate, o.group_id ginfo,g.group_name,d.device_no devno,d.device_sim p,
                    d.device_pass dpass,d.install_addr iaddr,o.customer_id cinfo,o.object_kind okind,o.userdef_flag uflag,o.remark,
                    dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                    convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                from cfg_user_purview p,cfg_device d, cfg_object o, cfg_group g,sys_device_type  dt
                where p.user_id = $user_id and p.purview_id = 1000 and d.object_id = o.object_id and o.group_id = g.group_id and d.dtype_id=dt.dtype_id
                    and o.group_id in (select * from dbo.fn_group4user($user_id)) and d.device_no ='$cat->device_no' ";
            $doquery = $db->query($sql);
            if ($doquery > 0) {
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data by device_no of device and object", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "No data available", 0, "", "");
                } else {
                    PrintJSON("", "data is no available", 0);
                }
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function changeGPS($gps)
    {
        try {

            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            date_default_timezone_set("Asia/Vientiane");
            $now_date = date("Y-m-d h:i:s");
            $time_zone = (float) $_SESSION['timezone'];
            // echo $time_zone;die();
            date_default_timezone_set("Asia/Vientiane");
            $sql = "select o.object_id,object_kind,object_flag,userdef_flag,customer_id,group_id,password,time_zone,driver_job_number,remark,
                        device_no,device_sim,device_pass,dtype_id,device_state,online,valid,convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) install_time,install_addr,convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) last_stamp
                        from cfg_object as o
                        INNER JOIN cfg_device as d ON o.object_id = d.object_id
                        where d.device_no = '$gps->devno'";
            $data_old = $db->query($sql);

            $object_kind = $data_old[0]['object_kind'];
            $object_flag = $data_old[0]['object_flag'];
            $userdef_flag = $data_old[0]['userdef_flag'];
            $customer_id = $data_old[0]['customer_id'];
            $group_id = $data_old[0]['group_id'];
            $password = $data_old[0]['password'];
            $time_zones = $data_old[0]['time_zone'];
            $driver = $data_old[0]['driver_job_number'];
            $remark = $data_old[0]['remark'];
            $device_no = $data_old[0]['device_no'];
            $device_sim = $data_old[0]['device_sim'];
            $device_pass = $data_old[0]['device_pass'];
            $dtype_id = $data_old[0]['dtype_id'];
            $device_state = $data_old[0]['device_state'];
            $online = $data_old[0]['online'];
            $valid = $data_old[0]['valid'];
            $install_time = $data_old[0]['install_time'];
            $install_addr = $data_old[0]['install_addr'];
            $last_stamp = $data_old[0]['last_stamp'];
            $object_id = $data_old[0]['object_id'];

            $subsql = "
                        set @code = -1
                            update cfg_device set device_sim = '$device_sim-$object_id', last_stamp ='$now_date' where object_id ='$object_id'
                        set @code = -2
                            update cfg_object set object_flag =N'$object_flag-$object_id', remark=N'$gps->remark' where object_id ='$object_id'

                        declare @purview    int
                        exec @purview = dbo.p_user_have_purview $user_id, 1090, 'A'
                        if @purview > 0
                        begin
                            set @code = -3
                            declare @olimit int
                            select @olimit = limit_object from sys_user where user_id = $user_id;
                            if @olimit > 0 or $user_id = 1
                            begin
                                insert into cfg_device (object_id, dtype_id, device_state, device_no, device_sim, device_pass, install_time, install_addr, last_stamp)
                                values (0, $dtype_id, $device_state, '$gps->new_devno', '$device_sim', '$device_pass', '$now_date', '$install_addr', '$last_stamp' )
                                set @code = -4
                                insert into cfg_object (customer_id, group_id, object_kind, object_flag, userdef_flag, time_zone, driver_job_number, remark)
                                values ($customer_id, $group_id, $object_kind, N'$object_flag', N'$userdef_flag', $time_zones, N'$driver', N'$remark')
                                set @objid = @@identity
                                set @code = -5
                                update cfg_device set object_id = o.object_id from cfg_object o
                                where device_no = '$gps->new_devno' and o.object_flag = N'$object_flag'
                                if $user_id <> 1
                                    update sys_user set limit_object = (@olimit -1) where user_id = $user_id;
                                set @code = -6

                                if '$driver' = '-1'
                                    delete from dat_rfid_last where device_no = '$gps->new_devno'
                                else
                                    begin
                                        if not exists(select * from dbo.dat_rfid_last where device_no = '$gps->new_devno')
                                        begin
                                            insert into dbo.dat_rfid_last
                                                (device_no, rfid)
                                            values
                                                ('$gps->new_devno', (select rfid from cfg_driver where job_number = '$driver'))
                                        end
                                        else
                                            update dbo.dat_rfid_last
                                            set rfid = (select rfid from cfg_driver where job_number = '$driver')
                                            where device_no = '$gps->new_devno'
                                    end
                                set @code = 0
                            end
                            else
                                set @code = -7
                        end
                        else
                            set @code = -20";
            // echo $subsql;die();

            $sql = "declare @code int, @objid int
                            begin try
                                begin tran
                                $subsql
                                commit tran
                            end try
                            begin catch
                                rollback tran
                            end catch

                            select @code as errcode, @objid as objectid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            $object_id = $data[0]['objectid'];
            /** TOUYARA* CREATE DEFAULT SERVICES FOR NEW ADDED OBJECT 25MAR2020*/
            if ($error_code == 0) {
                $q = "insert  [ANB-GPSDB].[dbo].[CFG_SERVICES] ([OBJECT_ID])
                        SELECT DISTINCT CB.OBJECT_ID
                        FROM [ANB-GPSDB].[dbo].[CFG_OBJECT] CB
                        WHERE NOT EXISTS (SELECT * FROM  [ANB-GPSDB].[dbo].[CFG_SERVICES] C
                                    WHERE CB.[OBJECT_ID] = C.[OBJECT_ID])";
                $db->query($q);
                $q = "update  [ANB-GPSDB].[dbo].[CFG_SERVICES] SET
                        [SPEED_ALARM] = 90
                        ,[IDLE_ALARM] = 3
                        ,[TRACKER_LOW_BAT]=50
                        ,[CAR_LOW_BAT]=9
                        ,[FATIGUE_DRIVING]=6
                        ,[OBD_MILEAGE]=0
                        ,[OBD_MILEAGE_BY]=1
                        ,[ENGINE_HOUR]=0
                        ,[ENGINE_HOUR_BY]=2
                        ,[MIN_MOVING_SPEED]=2
                        ,[MIN_IDLE_SPEED]=5
                        ,[DETECT_STOP_USING]=1
                        ,[FUEL_TANK_CAPACITY]=55
                        ,[FUEL_UPLOAD_TYPE]=1
                        /*,[STATE_EVENT_TABLE]= '12293,12294,12313,12314,8197'*/
                        ,[STATE_EVENT_TABLE]= '12293,12294,12313,12314,8197,4170,8208,8209,20482,4171,4173,12299'
                        ,[FUEL_100_KM]=9
                        ,[OBJECT_VOLTAGE]=0
                        ,[ALLOW_TEMP_FROM]=0
                        ,[ALLOW_TEMP_TO]=0
                        ,[ENABLE_NOTIFICATION]=0
                        ,[MIL_MAINTENANCE_ENABLE] =0
                        ,[MIL_MAINTENANCE_VALUE] = 0
                        ,[MIL_MAINTENANCE_LAST] =0
                        ,[ENG_MAINTENANCE_ENABLE]=0
                        ,[ENG_MAINTENANCE_VALUE]=0
                        ,[ENG_MAINTENANCE_LAST]=0
                        ,[DAY_MAINTENANCE_ENABLE]=0
                        ,[DAY_MAINTENANCE_VALUE]=0
                        ,[DAY_MAINTENANCE_LAST] ='1900-01-01 00:00:00.000'
                    ";
                $db->query($q);

                $json = array("objid" => "$object_id", "message" => "change GPS Ok", "status" => "1");
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "change GPS OK", 1, "", "");
                } else {
                    echo json_encode($json);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "change GPS fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "change GPS fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "change GPS fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "change GPS fail error: " . $e, 0);
            }
        }
    }
    public function getInstall_date($objid)
    {
        $db = new db_mssql();
        $time_zone = (float) $_SESSION['timezone'];
        $sql = "select object_id,device_no,device_sim,device_pass,dtype_id,device_state,online,valid,valid,convert(varchar(20), dbo.fn_to_client_time(install_time, $time_zone*60), 20) install_time,install_addr,convert(varchar(20), dbo.fn_to_client_time(last_stamp, $time_zone*60), 20) last_stamp from cfg_device where object_id='$objid'";
        $data = $db->query($sql);
        if ($this->socket == 1) {
            return Responese($this->command, $data, "date install of device", 1, "", "");
        } else {
            echo json_encode($data);
        }
    }
    public function get_details_categroy($get)
    {
        $db = new db_mssql();
        $sql = "select d.object_id,device_no,device_sim,device_pass,device_state,online,valid,install_time,install_addr,last_stamp,
                object_kind,object_flag,userdef_flag,dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,driver_job_number as driver
                from cfg_device as d INNER JOIN cfg_object as o ON d.object_id = o.object_id where device_no IN (";
        // print_r($get);die();
        for ($i = 0; $i < sizeof($get); $i++) {
            $devno = $get[$i]['imei_references'];

            if ($i == sizeof($get) - 1) {
                $sql .= "'$devno') order by o.object_id desc ";
            } else {
                $sql .= "'$devno',";
            }
        } 
        $data = $db->query($sql);
        if ($this->socket == 1) {
            return Responese($this->command, $data, "data list of device and object by category", 1, "", "");
        } else {
            echo json_encode($data);
        }
    }
    public function serverExpired($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            if ($cat->page == "" && $cat->limit == "") {
                $sql = "
                        select o.object_id,object_kind,object_flag,userdef_flag,customer_id,o.group_id,g.group_name,password,time_zone,driver_job_number,remark,
                        device_no,device_sim,device_pass,dtype_id,device_state,online,valid,
                        dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                        convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp,install_addr,
                        convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                        from cfg_object as o
                        INNER JOIN cfg_device as d ON o.object_id = d.object_id
                        INNER JOIN cfg_group as g ON o.group_id = g.group_id
                        where d.last_stamp between '$cat->firstdate' and '$cat->lastdate'";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     driver_job_number like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' ) ";
                }
                $sql .= "order by o.object_id desc";
                $doquery = $db->query($sql);
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list server expired of device", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $offset = (($cat->page - 1) * $cat->limit);

                $sql = "
                        select o.object_id,object_kind,object_flag,userdef_flag,customer_id,o.group_id,g.group_name,password,time_zone,driver_job_number,remark,
                        device_no,device_sim,device_pass,dtype_id,device_state,online,valid,
                        dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                        convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp,install_addr,
                        convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                        from cfg_object as o
                        INNER JOIN cfg_device as d ON o.object_id = d.object_id
                        INNER JOIN cfg_group as g ON o.group_id = g.group_id
                        where d.last_stamp between '$cat->firstdate' and '$cat->lastdate'";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     driver_job_number like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' ) ";
                }
                $sql_page = "order by o.object_id desc offset $offset rows fetch next $cat->limit rows only  ";
                // echo $sql.$sql_page;die();
                $doquery = $db->query($sql);

                if ($doquery > 0) {
                    $count = sizeof($doquery);
                    if ($count > 0) {
                        $data = $db->query($sql . $sql_page);
                        $list1 = json_encode($data);
                    }
                } else {
                    $list1 = json_encode([]);
                    $count = 0;
                }

                $number_count = $count;
                $total_page = ceil($number_count / $cat->limit);
                $list3 = json_encode($total_page);
                $json = "{  \"Data\":$list1,
                            \"Page\":$cat->page,
                            \"Pagetotal\":$list3,
                            \"Datatotal\":$number_count
                        }";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list server expired of device", 1, "", "");
                } else {
                    echo $json;
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function deviceOnline($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            if ($cat->page == "" && $cat->limit == "") {
                $sql = "
                        select o.object_id,object_kind,object_flag,userdef_flag,customer_id,o.group_id,g.group_name,password,time_zone,driver_job_number,remark,
                        device_no,device_sim,device_pass,dtype_id,device_state,online,valid,
                        dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                        convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp,install_addr,
                        convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                        from cfg_object as o
                        INNER JOIN cfg_device as d ON o.object_id = d.object_id
                        INNER JOIN cfg_group as g ON o.group_id = g.group_id
                        where online=$cat->online ";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     driver_job_number like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' ) ";
                }
                $sql .= "order by o.object_id desc";
                $doquery = $db->query($sql);
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list of device online", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $offset = (($cat->page - 1) * $cat->limit);

                $sql = "
                        select o.object_id,object_kind,object_flag,userdef_flag,customer_id,o.group_id,g.group_name,password,time_zone,driver_job_number,remark,
                        device_no,device_sim,device_pass,dtype_id,device_state,online,valid,
                        dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                        convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp,install_addr,
                        convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                        from cfg_object as o
                        INNER JOIN cfg_device as d ON o.object_id = d.object_id
                        INNER JOIN cfg_group as g ON o.group_id = g.group_id
                        where online=$cat->online ";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     driver_job_number like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' ) ";
                }
                $sql_page = "order by o.object_id desc offset $offset rows fetch next $cat->limit rows only  ";
                // echo $sql.$sql_page;die();
                $doquery = $db->query($sql);

                if ($doquery > 0) {
                    $count = sizeof($doquery);
                    if ($count > 0) {
                        $data = $db->query($sql . $sql_page);
                        $list1 = json_encode($data);
                    }
                } else {
                    $list1 = json_encode([]);
                    $count = 0;
                }

                $number_count = $count;
                $total_page = ceil($number_count / $cat->limit);
                $list3 = json_encode($total_page);
                $json = "{  \"Data\":$list1,
                            \"Page\":$cat->page,
                            \"Pagetotal\":$list3,
                            \"Datatotal\":$number_count
                        }";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list page of device online", 1, "", "");
                } else {
                    echo $json;
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function getDtype()
    {
        try {
            $db = new db_mssql();
            $sql = "select * from sys_device_type";
            $data = $db->query($sql);
            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data list of device type", 1, "", "");
            } else {
                echo $json;
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function searchGPS($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            if ($cat->page == "" && $cat->limit == "") {
                $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number driver,dv.driver_name, d.dtype_id dtype,dt.dtype_name, d.device_state dstate, o.group_id ginfo,g.group_name,d.device_no devno,d.device_sim p,
                    d.device_pass dpass,d.install_addr iaddr,o.customer_id cinfo,o.object_kind okind,o.userdef_flag uflag,o.remark,
                    dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                    convert(varchar(20), dbo.fn_to_client_time(d.install_time, $time_zone*60), 20) stamp, convert(varchar(20), dbo.fn_to_client_time(d.last_stamp, $time_zone*60), 20) estamp
                from cfg_user_purview p,cfg_device d, cfg_object o, cfg_group g,sys_device_type  dt,cfg_driver dv
                where p.user_id = $user_id and p.purview_id = 1000 and d.object_id = o.object_id and o.group_id = g.group_id and d.dtype_id=dt.dtype_id and o.driver_job_number=dv.job_number
                    and o.group_id in (select * from dbo.fn_group4user($user_id))  ";
                if ((isset($cat->key) && $cat->key != "") && (isset($cat->value) && $cat->value != "")) {
                    $sql .= "and $cat->key ='$cat->value' ";
                }
                $sql .= "order by o.object_id desc ";
                $doquery = $db->query($sql);
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list of device", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $offset = (($cat->page - 1) * $cat->limit);

                $sql = "select o.object_id objid, o.object_flag oflag, o.driver_job_number driver, d.dtype_id
                        dtype,dt.dtype_name, d.device_state dstate,d.online, o.group_id ginfo,g.group_name,d.device_no devno,d.device_sim p,
                        d.device_pass dpass,d.install_addr iaddr,o.customer_id cinfo,o.object_kind
                        okind,o.userdef_flag uflag,o.remark,
                        dbo.fn_sec2time(60 * isnull(o.time_zone, datediff(minute, getutcdate(), getdate())), '-hm') ztime,
                        convert(varchar(20), dbo.fn_to_client_time(d.install_time, 7*60), 20) stamp, convert(varchar(20),
                        dbo.fn_to_client_time(d.last_stamp, 7*60), 20) estamp
                        from cfg_device d, cfg_object o, cfg_group g,sys_device_type dt
                        where  d.object_id = o.object_id and o.group_id = g.group_id and
                        d.dtype_id=dt.dtype_id
                        and o.group_id in (select * from dbo.fn_group4user(1)) ";
                if ((isset($cat->key) && $cat->key != "") && (isset($cat->value) && $cat->value != "")) {
                    $sql .= "and $cat->key ='$cat->value' ";
                }
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                     o.object_flag like '%$cat->keyword%' or
                                     o.object_id like '%$cat->keyword%' or
                                     d.device_no like '%$cat->keyword%' or
                                     d.device_sim like '%$cat->keyword%' or
                                     g.group_name like '%$cat->keyword%' or
                                     dt.dtype_name like '%$cat->keyword%' ) ";
                }
                $sql_page = "order by o.object_id desc offset $offset rows fetch next $cat->limit rows only  ";
                // echo $sql.$sql_page;die();
                $doquery = $db->query($sql);

                if ($doquery > 0) {
                    $count = sizeof($doquery);
                    if ($count > 0) {
                        $data = $db->query($sql . $sql_page);
                        $list1 = json_encode($data);
                    }
                } else {
                    $list1 = json_encode([]);
                    $count = 0;
                }

                $number_count = $count;
                $total_page = ceil($number_count / $cat->limit);
                $list3 = json_encode($total_page);
                $json = "{  \"Data\":$list1,
                            \"Page\":$cat->page,
                            \"Pagetotal\":$list3,
                            \"Datatotal\":$number_count
                        }";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list of device", 1, "", "");
                } else {
                    echo $json;
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
}
