<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";

class DriverController
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

    public function addDriver($dr)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $havePhoto = true;
            if (strcmp($dr->p, "no") == 0) {
                $havePhoto = false;
            } else {
                $p = "0x" . bin2hex(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dr->p)));
            }
            if ($havePhoto) {
                $subsql = "
							declare @purview    int
							exec @purview = dbo.p_user_have_purview $user_id, 1300, 'A'
							if @purview > 0
							begin
								   set @code = -1
								   insert into dbo.cfg_driver
								   (job_number,driver_name,sex,customer_id,user_id,is_primary,password,phone,ident,rfid,license,issue_date,expire_date,company,address,remark,photo)
								   values
								   (N'$dr->jno',N'$dr->name',$dr->sex,0,$user_id,$dr->ip,'','$dr->tel','$dr->id','$dr->rfid','$dr->l','$dr->isd','$dr->exd',N'$dr->co',N'$dr->addr',N'$dr->r',$p)
								   set @code = 0
							end
							else
								set @code = -20";
            } else {
                $subsql = "
							declare @purview    int
							exec @purview = dbo.p_user_have_purview $user_id, 1300, 'A'
							if @purview > 0
							begin
								   set @code = -1
								   insert into dbo.cfg_driver
								   (job_number,driver_name,sex,customer_id,user_id,is_primary,password,phone,ident,rfid,license,issue_date,expire_date,company,address,remark)
								   values
								   (N'$dr->jno',N'$dr->name',$dr->sex,0,$user_id,$dr->ip,'','$dr->tel','$dr->id','$dr->rfid','$dr->l','$dr->isd','$dr->exd',N'$dr->co',N'$dr->addr',N'$dr->r')
								   set @code = 0
							end
							else
								set @code = -20";

            }
            //    echo $subsql;die();
            $sql = "declare @code int
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
            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add driver Ok! ", 1, "", "");
                } else {
                    PrintJSON("", "add driver Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add driver fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "add driver fail error: " . $error_code, 0);
                }
            }

        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "add driver fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "add driver fail error: " . $e, 0);
            }
        }
    }
    public function updateDriver($dr)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $havePhoto = true;
            if (strcmp($dr->p, "no") == 0) {
                $havePhoto = false;
            } else {
                $p = "0x" . bin2hex(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dr->p)));
            }
            if ($havePhoto) {
                $subsql = "
                    declare @purview    int,
                            @have       int
                    exec @purview = dbo.p_user_have_purview $user_id, 1300, 'M'
                    exec @have = dbo.p_user_have_driver $user_id, N'$dr->jno'
                    if @purview > 0 and @have > 0
                    begin
                           set @code = -1

                           declare @oldrfid varchar(40)
                           select @oldrfid = rfid from cfg_driver where job_number = N'$dr->jno'

                           if '$dr->rfid' != @oldrfid
                           begin
                                update dbo.dat_rfid_last
                                set rfid = '$dr->rfid'
                                where rfid = @oldrfid
                           end

                           update dbo.cfg_driver set driver_name = N'$dr->name', sex = $dr->sex, is_primary = $dr->ip,
                           phone = '$dr->tel', ident = '$dr->id', rfid = '$dr->rfid', license = '$dr->l', issue_date = '$dr->isd',expire_date = '$dr->exd',
                           company = N'$dr->co',address = N'$dr->addr', remark = N'$dr->r',photo =$p where job_number = N'$dr->jno'
                           set @code = 0
                    end
                    else
                        set @code = -20";
            } else {
                $subsql = "
                    declare @purview    int,
                            @have       int
                    exec @purview = dbo.p_user_have_purview $user_id, 1300, 'M'
                    exec @have = dbo.p_user_have_driver $user_id, N'$dr->jno'
                    if @purview > 0 and @have > 0
                    begin
                           set @code = -1

                           declare @oldrfid varchar(40)
                           select @oldrfid = rfid from cfg_driver where job_number = N'$dr->jno'

                           if '$dr->rfid' != @oldrfid
                           begin
                                update dbo.dat_rfid_last
                                set rfid = '$dr->rfid'
                                where rfid = @oldrfid
                           end

                           update dbo.cfg_driver set driver_name = N'$dr->name', sex = $dr->sex, is_primary = $dr->ip,
                           phone = '$dr->tel', ident = '$dr->id', rfid = '$dr->rfid', license = '$dr->l', issue_date = '$dr->isd',expire_date = '$dr->exd',
                           company = N'$dr->co',address = N'$dr->addr', remark = N'$dr->r' where job_number = N'$dr->jno'
                           set @code = 0
                    end
                    else
                        set @code = -20";
            }
            //  echo $subsql;die();
            $sql = "declare @code int
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
                    return Responese($this->command, "", "update driver Ok! ", 1, "", "");
                } else {
                    PrintJSON("", "update driver Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update driver fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "update driver fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "update driver fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "update driver fail error: " . $e, 0);
            }
        }
    }
    public function deleteDriver($dr)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $subsql = "
            declare @purview    int,
                    @have       int
            exec @purview = dbo.p_user_have_purview $user_id, 1300, 'D'
            exec @have = dbo.p_user_have_driver $user_id, '$dr->jno'
            if @purview > 0 and @have > 0
            begin
                set @code = -1
                if exists(select object_id from cfg_object where driver_job_number = N'$dr->jno')
                begin
                    set @code = -2;
                end
                else
                begin
                    delete from cfg_driver where job_number = N'$dr->jno'
                    set @code = 0
                end
            end
            else
                set @code = -20";
//    echo $subsql;die();
            $sql = "declare @code int
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
            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "driver id: " . $dr->jno . " delete Ok!", 1, "", "");
                } else {
                    PrintJSON("", "driver id: " . $dr->jno . " delete Ok!", 1);
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
    public function DriverList($dr)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            if (isset($dr->jno)) {
                $jobnumber = $dr->jno;
                //query one driver
                $time_zone = (float) $_SESSION['timezone'];
                $sql = "select d.job_number j, d.driver_name n, d.sex s, d.is_primary ip,
						d.phone p, d.ident id, d.rfid rfid, d.license l,
						convert(varchar(10), dbo.fn_to_client_time(d.issue_date, $time_zone*60), 20) isd,
						convert(varchar(10), dbo.fn_to_client_time(d.expire_date, $time_zone*60), 20) exd,
						d.company co, d.address addr, d.photo ph, d.remark r
						from dbo.cfg_driver d
						where d.job_number = '$jobnumber'";
                $driverlist = $db->query($sql);
                foreach ($driverlist as $row) {
                    $row['ph'] = $row['ph'] == null ? "" : 'data:image/jpeg;base64,' . base64_encode($row['ph']);
                    $output[] = $row;
                }
                $json = array2json($output);
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list one of driver", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                //query driver list
                $sql = "select d.job_number j, d.driver_name n, d.phone p, d.license l , d.rfid r from cfg_user_purview p, dbo.cfg_driver d where p.user_id = $user_id and p.purview_id = 1000 and d.job_number in
						(select driver_job_number from dbo.cfg_object where group_id in ( select group_id from dbo.fn_group4user($user_id)))
						union
						select d.job_number j, d.driver_name n, d.phone p, d.license l , d.rfid r from cfg_user_purview p, dbo.cfg_driver d where p.user_id = $user_id and p.purview_id = 1000 and d.user_id = $user_id
						union
						select d.job_number j, d.driver_name n, d.phone p, d.license l , d.rfid r from cfg_user_purview p, dbo.cfg_driver d where p.user_id = $user_id and p.purview_id = 1000 and d.user_id in (select user_id from dbo.fn_user_tree($user_id))
						";
                $driverlist = $db->query($sql);

                $sql_pur = "select purview_id pid, isnull(purview,'') p from cfg_user_purview where user_id = $user_id and purview_id = 1300";
                $upurview = $db->query($sql_pur);

                $list = array2json($driverlist);
                $pr = array2json($upurview);
                $json = "{'list': $list, 'pur': $pr}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data list all of driver", 1, "", "");
                } else {
                    echo $json;
                }
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function driverListPage($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            $offset = (($cat->page - 1) * $cat->limit);

            $sql = "select d.job_number j, d.driver_name n, d.sex s, d.is_primary ip,
                d.phone p, d.ident id, d.rfid rfid, d.license l,
                convert(varchar(10), dbo.fn_to_client_time(d.issue_date, $time_zone*60), 20) isd,
                convert(varchar(10), dbo.fn_to_client_time(d.expire_date, $time_zone*60), 20) exd,
                d.company co, d.address addr, d.photo ph, d.remark r
                from dbo.cfg_driver d ";
            if (isset($cat->keyword) && $cat->keyword != "") {
                $sql .= "where
                                     job_number like '%$cat->keyword%' or
                                     driver_name like N'%$cat->keyword%' or
                                     phone like '%$cat->keyword%' or
                                     address like N'%$cat->keyword%' or
                                     rfid like N'%$cat->keyword%' or
                                     company like N'%$cat->keyword%' ";
            }
            $sql_page = "order by job_number desc offset $offset rows fetch next $cat->limit rows only  ";
            // echo $sql.$sql_page;die();
            $doquery = $db->query($sql);

            if ($doquery > 0) {
                $count = sizeof($doquery);
                if ($count > 0) {
                    $driverlist = $db->query($sql . $sql_page);
                    foreach ($driverlist as $row) {
                        $row['ph'] = $row['ph'] == null ? "" : 'data:image/jpeg;base64,' . base64_encode($row['ph']);
                        $data[] = $row;
                    }
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
                return Responese($this->command, $json, "data list page of driver", 1, "", "");
            } else {
                echo $json;
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function getDriver()
    {
        try {

            $db = new db_mssql();
            $sql = "select job_number,driver_name from cfg_driver";
            $data = $db->query($sql);
            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command, $json, "get data of driver", 1, "", "");
            } else {
                echo $json;
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
}
