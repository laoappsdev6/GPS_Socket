<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";
require_once __DIR__ . "./../config/user.config.php";

class UserController
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
    public function userlist($user_id = null)
    {
        $db = new db_mssql();
        //user list
        if ($user_id) {
            //query one
            $time_zone = (float) $_SESSION['timezone'];
            $sql = "select user_id usrid, user_name uname, login_name login, login_pass upass, isnull(email,'') email,
                case when mail_offset is null then '12:00' else
                convert(varchar(5), convert(time, dbo.fn_to_client_time( dateadd(mi, isnull(mail_offset,0), 0), $time_zone*60),20))
                end rtime,
                isnull(mail_report, 0) rmail, mail_type mtype, valid, user_phone uphone, limit_object olimit from sys_user
                where user_id = $user_id";
            $ulist = $db->query($sql);
            $json = array2json($ulist);
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data of user!", 1, "", "");
            } else {
                echo $json;
            }
        } else {
            //query all
            $user_id = $_SESSION["uid"];
            $time_zone = (float) $_SESSION['timezone'];
            $sql_list = "select u.user_id usrid, u.user_name uname, u.login_name login, convert(varchar(20),dbo.fn_to_client_time(u.expire_time, $time_zone*60), 20) etime, u.valid,
                         u.user_phone p, u.limit_object l from cfg_user_purview p,sys_user u
                         where p.user_id = $user_id and p.purview_id = 3000 and u.user_id in (select user_id from dbo.fn_user_tree($user_id))
                         order by u.owner_id, u.user_name";
            //  echo  $sql_list ;
            //  die();
            $ulist = $db->query($sql_list);
            $list = array2json($ulist);
            $sql_pur = "select purview_id pid, isnull(purview,'') p from cfg_user_purview where user_id = $user_id and (purview_id = 3200 or purview_id = 3400)";
            $upurview = $db->query($sql_pur);
            $purview = array2json($upurview);
            $json =  "{\"self\":$user_id, \"list\":$list, \"pur\": $purview}";
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data list all of user ", 1, "", "");
            } else {
                echo $json;
            }
        }
    }
    public function addUser($u)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone_ = Config::$_timeZone;
            $idLat_ = (double) Config::$_idLat;
            $idLng_ = (double) Config::$_idLng;
            $idDate_ = Config::$_idDate;
            $idTime_ = Config::$_idTime;
            $idSound_ = Config::$_idSound;
            $idPopup_ = Config::$_idPopup;
            $unitSpeed_ = Config::$_unitSpeed;
            $unitDist_ = Config::$_unitDist;
            $unitFuel_ = Config::$_unitFuel;
            $unitTemp_ = Config::$_unitTemp;

            $email = isset($u->email_) ? $u->email_ : "";
            $rtime = isset($u->rtime_) ? $u->rtime_ : "11:00";
            $rmail = isset($u->rmail_) ? $u->rmail_ : "0";
            $mtype = isset($u->mtype_) ? $u->mtype_ : "";
            isset($u->serf_) ? $u->mtype_ : "";
            $socket = isset($_SESSION['socket']) ? $_SESSION['socket'] : "";

            $subsql = "
			declare @purview    int
			exec @purview = dbo.p_user_have_purview $user_id, 3200, 'A'
			if @purview > 0
			begin
				set @code = -1
				declare @remain int

				if $user_id <> 1
					select @remain = (limit_object - $u->olimit_) from sys_user where user_id = $user_id;
				else
					set @remain = 0
				if @remain >= 0 and $u->olimit_ >=0
				begin
					declare @user int
					set @code = -2
					insert into sys_user (user_name, login_name, login_pass, email, mail_offset, mail_report, mail_type, valid, owner_id, user_phone, limit_object)
						values (N'$u->uname_', N'$u->login_', N'$u->upass_', N'$email',
							datediff(mi, 0, convert(time, dbo.fn_to_server_time(convert(time, '$rtime', 20), $time_zone_ * 60), 20)),
							$rmail, '$mtype', $u->valid_, $user_id, N'$u->uphone_', $u->olimit_)
					set @code = -3
					set @user = scope_identity()

					delete cfg_user_group where user_id = @user
					insert into cfg_user_group
					select @user, group_id from cfg_group where group_id in ($u->group_)
					set @code = -4

					delete web_default where user_id = @user
					insert into dbo.web_default (user_id, def_lat, def_lng, def_date_fmt, def_time_fmt, def_sound_alarm, def_popup_alarm, unit_distance, unit_fuel, unit_temperature, unit_speed)
					values (@user, $idLat_, $idLng_, '$idDate_', '$idTime_', $idSound_, $idPopup_, $unitDist_, $unitFuel_, $unitTemp_, $unitSpeed_)
					set @code = -5

					if $user_id <> 1
						update sys_user set limit_object = @remain where user_id = $user_id
					set @code = 0
				end
				else
					set @code = -6
			end
			else
				set @code = -20";
            // echo $subsql;die();
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
            //echo $subsql;die();
            $data = $db->queryLastDS($sql);

            $error_code = $data[0]['errcode'];
            /** TOUYARA* CREATE DEFAULT ACCESS PERMISSION FOR NEW ADDED USER 25MAR2020*/
            if ($error_code == 0) {
                $q = newUserPermission();

                $d = $db->queryLastDS($q);
                $error_c1 = $d[0]['errcode'];

                if ($error_c1 != 0) {
                    if ($this->socket == 1) {
                        return Responese($this->command, "", "error assigned default access for new user" . $error_c1, 0, "", "");
                    } else {
                        PrintJSON("", "error assigned default access for new user" . $error_c1, 0);
                    }

                } else {
                    if ($this->socket == 1) {
                        return Responese($this->command, "", "add new user Ok!", 1, "", "");
                    } else {
                        PrintJSON("", "add new user Ok!", 1);
                    }
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "error cant add new user!" . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "error cant add new user" . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            // print_r($e);
            if ($this->socket == 1) {
                return Responese($this->command, $e, "error cant add new user!", 0, "", "");
            } else {
                PrintJSON("", "error cant add new user" . $e, 0);
            }

        }
    }
    public function updateUser($u)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone_ = Config::$_timeZone;

            $email = isset($u->email_) ? $u->email_ : "";
            $rtime = isset($u->rtime_) ? $u->rtime_ : "11:00";
            $rmail = isset($u->rmail_) ? $u->rmail_ : "0";
            $mtype = isset($u->mtype_) ? $u->mtype_ : "";

            if ($user_id != $u->usrid_) {

                //modify sub user
                $subsql = "
					declare @purview    int,
							@have       int
					exec @purview = dbo.p_user_have_purview $user_id, 3200, 'M'
					exec @have = dbo.p_user_have_subuser $user_id, $u->usrid_
					if @purview > 0 and @have > 0
					begin
						set @code = -1
						declare @remain     int,
								@total      int,
								@dist       int

						if $user_id <> 1
						begin
							select @total = limit_object from sys_user where user_id = $u->usrid_;
							set @dist = $u->olimit_ - @total;
							select @remain = (limit_object - @dist) from sys_user where user_id = $user_id;
						end
						else
							set @remain = 0
						if @remain >= 0 and $u->olimit_ >=0
						begin
							set @code = -2
							update sys_user set user_name = N'$u->uname_', login_name = N'$u->login_', login_pass = N'$u->upass_', email = N'$email',
								mail_offset = datediff(mi, 0, convert(time, dbo.fn_to_server_time(convert(time, '$rtime', 20), $time_zone_ * 60), 20)), mail_report = $rmail, mail_type = '$mtype', valid = $u->valid_, user_phone = N'$u->uphone_', limit_object = $u->olimit_
							where user_id = $u->usrid_
							delete cfg_user_group where user_id = $u->usrid_ and group_id in (
								select group_id from dbo.fn_group4user($user_id)
							)
							set @code = -3
							insert into cfg_user_group
							select $u->usrid_, group_id from cfg_group where group_id in ($u->group_)
							set @code = -4

							if $user_id <> 1
								update sys_user set limit_object = @remain where user_id = $user_id
							set @code = 0
						end
						else
							set @code = -5
					end
					else
						set @code = -20
                    ";
                //echo $subsql;die();
            } else {
                //modify self
                $subsql = "
					declare @purview    int
					exec @purview = dbo.p_user_have_purview $user_id, 3200, 'M'
					if @purview > 0
					begin
						set @code = -2
						update sys_user set user_name = N'$u->uname_', login_name = N'$u->login_', login_pass = N'$u->upass_', email = N'$email',
						mail_offset = datediff(mi, 0, convert(time, dbo.fn_to_server_time(convert(time, '$rtime', 20), $time_zone_ * 60), 20)), mail_report = $rmail, mail_type = '$mtype', user_phone = N'$u->uphone_'
						where user_id = $u->usrid_
						set @code = 0
					end
					else
						set @code = -20
					";
            }
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
            /** TOUYARA* CREATE DEFAULT ACCESS PERMISSION FOR NEW ADDED USER 25MAR2020*/
            if ($error_code == 0) {
                if ($u->self_ != 0) {
                    $_SESSION['pass'] = $u->upass_;
                    $_SESSION['uname'] = $u->uname_;
                    $_SESSION['email'] = $u->email_;
                    $_SESSION['rtime'] = $u->rtime_;
                    $_SESSION['rmail'] = $u->rmail_;
                    $_SESSION['mtype'] = $u->mtype_;
                }
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update user Ok!", 1, "", "");
                } else {
                    PrintJSON("", "update user Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update fail error" . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "update fail error" . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "update fail error" . $e, 0, "", "");
            } else {
                PrintJSON("", "update fail error" . $e, 0);
            }

        }
    }
    public function deleteUser($u)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $subsql = "
                        declare @purview    int,
                                @have       int
                        exec @purview = dbo.p_user_have_purview $user_id, 3200, 'D'
                        exec @have = dbo.p_user_have_subuser $user_id, $u->usrid_
                        if @purview > 0 and @have > 0
                        begin
                            set @code = -2
                            exec p_delete_user $u->usrid_
                        end
                        else
                            set @code = -20";

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
            @$error_code = $data[0]['errcode'];
            if ($error_code != 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "cant delete this user! error:" . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "cant delete this user! error: " . $error_code, 0);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, ""," id: " . $u->usrid_ . " delete Ok!", 1, "", "");
                } else {
                    PrintJSON("", "id: " . $u->usrid_ . " delete Ok!", 1);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "cant delete this user! error:" . $e, 0, "", "");
            } else {
                PrintJSON("", "cant delete this user! error: " . $e, 0);
            }

        }
    }
    public function changePassword($u)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $subsql = "
                            set @code = -1
                            update sys_user set login_pass='$u->new_password' where user_id='$u->user_id';
                            set @code = 0";
            // echo $subsql;die();
            $sql = "declare @code int
                                begin
                                    $subsql
                                end
                                select @code as errcode";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "change password Ok! ", 1, "", "");
                } else {
                    PrintJSON("", "change password Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "change password fail! error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "change password fail! error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "change password fail! error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "change password fail! error: " . $e, 0);
            }

        }
    }
    public function UserListPage($cat)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            if ($cat->page == "" && $cat->limit == "") {
                $sql = "select u.user_id usrid, u.user_name uname, u.login_name login, convert(varchar(20),dbo.fn_to_client_time(u.expire_time, $time_zone*60), 20) etime, u.valid,
                u.user_phone p, u.limit_object l from cfg_user_purview p,sys_user u
                where p.user_id = $user_id and p.purview_id = 3000 and u.user_id in (select user_id from dbo.fn_user_tree($user_id))
                order by u.user_id desc";
                $doquery = $db->query($sql);
                $list = json_encode($doquery);
                $json = "{\"Data\":$list}";
                if ($this->socket == 1) {
                    return Responese($this->command,$doquery, "Data all of user", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                $offset = (($cat->page - 1) * $cat->limit);

                $sql = "select u.user_id usrid, u.user_name uname, u.login_name login, convert(varchar(20),dbo.fn_to_client_time(u.expire_time, $time_zone*60), 20) etime, u.valid,
                u.user_phone p, u.limit_object l from cfg_user_purview p,sys_user u
                where p.user_id = $user_id and p.purview_id = 3000 and u.user_id in (select user_id from dbo.fn_user_tree($user_id)) ";
                if (isset($cat->keyword) && $cat->keyword != "") {
                    $sql .= "and (
                                    u.user_id like '%$cat->keyword%' or
                                     u.user_name like '%$cat->keyword%' or
                                     u.login_name like '%$cat->keyword%' or
                                     u.user_phone like '%$cat->keyword%' ) ";
                }
                $sql_page = "order by u.user_id desc offset $offset rows fetch next $cat->limit rows only  ";
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
                    return Responese($this->command,$json, "Data list page of user", 1, "", "");
                } else {
                    echo $json;
                }
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function UserListGroup($get)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            $sql = "select g.group_id,g.group_name,g.group_parent,u.user_id usrid, u.user_name uname, u.login_name login,login_pass, convert(varchar(20),
                        dbo.fn_to_client_time(u.expire_time, $time_zone*60), 20) etime, u.valid,u.user_phone p, u.limit_object
                        from cfg_group as g
                        INNER JOIN cfg_user_group as gu ON g.group_id = gu.group_id
                        INNER JOIN sys_user as u ON gu.user_id = u.user_id
                        where g.group_id IN($get->group_id)";
            $data = $db->query($sql);
            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command,$json, "data of user by group", 1, "", "");
            } else {
                echo $json;
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function userList_one($get)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $time_zone = (float) $_SESSION['timezone'];

            $sql = "select u.user_id usrid, u.user_name uname, u.login_name login, convert(varchar(20),
                    dbo.fn_to_client_time(u.expire_time, $time_zone*60), 20) etime, u.valid, u.user_phone phone, u.limit_object olimit
                    from sys_user u
                    where u.user_id='$get->user_id'";
            $data = $db->query($sql);

            $sql1 = "select * from cfg_user_group where user_id ='$get->user_id'";
            $data1 = $db->query($sql1);

            $id = "";
            for ($i = 0; $i < sizeof($data1); $i++) {
                $group_id = $data1[$i]['GROUP_ID'];
                if ($i == sizeof($data1) - 1) {
                    $id .= "$group_id";
                } else {
                    $id .= "$group_id,";
                }
                $data[0]['group_id'] = $id;
            }

            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command,$json, "data of user by id", 1, "", "");
            } else {
                echo $json;
            }

        } catch (Exception $e) {
            print_r($e);
        }
    }
}
