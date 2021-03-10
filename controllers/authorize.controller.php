<?php
require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";

class LoginController
{
    public $socket;
    public function __construct()
    {
        $this->socket = $_SESSION["socket"];
    }

    function checkLogin($u)
    {
        $iduser = trim($u->username);
        $idpass = $u->password;
        $time_zone = isset($u->timezone) ? (float)$u->timezone : 0;
        $sql = "select u.user_id uid, u.user_name uname, u.login_pass pass, u.email, 
        convert(varchar(5), convert(time, dbo.fn_to_client_time(dateadd(mi, isnull(u.mail_offset,0), 0), $time_zone*60),20)) rtime, 
        isnull(u.mail_report,0) rmail, mail_type mtype, u.valid valid, rr.role_name rname,
        w.def_lat lat, w.def_lng lng, w.def_date_fmt date_fmt, w.def_time_fmt time_fmt, isnull(w.def_sound_alarm,0) sond_alarm, isnull(w.def_popup_alarm,0) popup_alarm,
        isnull(w.unit_distance,0) ud, isnull(w.unit_fuel,0) uf, isnull(w.unit_temperature,0) ut, isnull(w.unit_speed,0) us
		from sys_user u
        left join web_default w on u.user_id = w.user_id
        left join (select ur.user_id, r.role_name from sys_role r, sys_user_role ur
            where r.role_id = ur.role_id) rr on rr.user_id = u.user_id
        where u.login_name = '$iduser'";
    //echo $sql;
    $db = new db_mssql();

    //print_r($db);
    $data = $db->query($sql); 
	$sql = "select count(*) okind from sys_object_kind";
    $okind = $db->query($sql);
    //print_r($okind);
    if (!empty($data) and !empty($okind)) {
        $row = $data[0];
        $object_kind = $okind[0];
        if ((int) $row['valid'] != 1) {
            if(isset($u->token)){
                if ($this->socket == 1) {
                    return Responese("login", "", "stopped", 0, "", "");
                } else {
                    echo json_encode(array('status'=>'stopped'));
                }
              }else{
                if ($this->socket == 1) {
                    return Responese("login", "", "stopped", 0, "", "");
                } else {
                    echo 'stopped';
                }
              }
        } else if ($row['pass'] != $idpass) {
            if(isset($u->token)){
                if ($this->socket == 1) {
                    return Responese("login", "", "invalid", 0, "", "");
                } else {
                    echo json_encode(array('status'=>'invalid'));
                }
              }else{
                if ($this->socket == 1) {
                    return Responese("login", "", "invalid", 0, "", "");
                } else {
                    echo 'invalid';
                }
              }
        } else {
            $_SESSION['uid'] = $row['uid'];
            $_SESSION['pass'] = $row['pass'];
            $_SESSION['maptype'] = isset($u->idmap)?$u->idmap:"";
            $_SESSION['uname'] = $row['uname'];
            $_SESSION['rname'] = $row['rname'];
            $_SESSION['email'] = $row['email'];
              $_SESSION['rtime'] = $row['rtime'];
            $_SESSION['rmail'] = $row['rmail'];
			$_SESSION['mtype'] = $row['mtype'];
            $_SESSION['timezone'] = $time_zone;
            $row['client_time_zone'] = $time_zone;
            $row['lang'] = $_SESSION['lang'];
            // if(isset($_POST['token'])){
            //     $row['lang'] = $_POST['lang'];
            // }
            // $_SESSION['logined'] = true;
            // $_SESSION['lat'] = empty($row['lat']) ? $default_latlng['lat'] : number_format(((double) $row['lat'] / 1000000) , 5);
            // $_SESSION['lng'] = empty($row['lng']) ? $default_latlng['lng'] : number_format(((double) $row['lng'] / 1000000), 5);
            // $_SESSION['zoom'] = $default_latlng['zoom'];
            // $_SESSION['date_fmt'] = empty($row['date_fmt']) ? $support_datefmt['yyyy-MM-dd'] : $support_datefmt[$row['date_fmt']];
            // $_SESSION['time_fmt'] = empty($row['time_fmt']) ? $support_timefmt['HH:mm:ss'] : $support_timefmt[$row['time_fmt']];
            //$_SESSION['datetime_fmt'] = $_SESSION['date_fmt'] . ' ' . $_SESSION['time_fmt'];
			$date_fmt_js = empty($row['date_fmt']) ? 'yyyy-MM-dd' : $row['date_fmt'];
            $time_fmt = empty($row['time_fmt']) ? 'HH:mm:ss' : $row['time_fmt'];
			$_SESSION['date_fmt_js'] = $date_fmt_js;
			$_SESSION['time_fmt_js'] = $time_fmt;
			$_SESSION['datetime_fmt_js'] = $date_fmt_js . ' ' . $time_fmt;
            $_SESSION['lang'] = $_SESSION['lang'];
			$_SESSION['sond_alarm'] = $row['sond_alarm'];
			$_SESSION['popup_alarm'] = $row['popup_alarm'];
			$_SESSION['object_kind'] = $object_kind['okind'];
            
            $row['okind']=$object_kind['okind'];
			$_SESSION['unit_distance'] = $row['ud'];
			$_SESSION['unit_fuel'] = $row['uf'];
			$_SESSION['unit_temperature'] = $row['ut'];
			$_SESSION['unit_speed'] = $row['us'];
			// die(json_encode($row));
            if(isset($u->token)){
                $json = array('status'=>'ok','token'=>registerToken($row));
                if ($this->socket == 1) {
                    return Responese("login", $json, "login ok", 1, "", "");
                } else {
                    echo json_encode($json);
                }
            }else{
                if ($this->socket == 1) {
                    return Responese("login", "", "ok", 1, "", "");
                } else {
                    echo 'ok';
                }
            }
        }
    } else if($db->error_code == 0){
        if(isset($u->token)){
            if ($this->socket == 1) {
                return Responese("login", "", "invalid", 1, "", "");
            } else {
                echo json_encode(array('status'=>'invalid'));
            }
          }else{
            if ($this->socket == 1) {
                return Responese("login", "", "invalid", 1, "", "");
            } else {
                echo 'invalid';
            }
          }
    } else {
        if(isset($u->token)){
            if ($this->socket == 1) {
                return Responese("login", "", "error", 1, "", "");
            } else {
                echo json_encode(array('status'=>'error'));
            }
          }else{
            if ($this->socket == 1) {
                return Responese("login", "", "error", 1, "", "");
            } else {
                echo 'error';
            }
          }
    }
    }
}
