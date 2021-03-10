<?php

error_reporting(E_ALL ^ E_NOTICE);
include_once 'common.inc.php';

function PrintJSON($data, $message, $status)
{

    $f = '{"data":"%s","message":"%s","status":"%s"}';
    if ($data) {
        if (sizeof($data) > 0) {
            return printf($f, json_encode($data), $message, $status);
        } else {
            return printf($f, json_encode([$data]), $message, $status);
        }
    } else {
        printf($f, "[]", $message, $status);
    }
}

function JsonValidate($message, $status)
{
    if (empty($data)) {
        $response = "{\"message\":\"$message\",\"status\":\"$status\"}";
    } else {
        $response = "{\"message\":\"$message\",\"status\":\"$status\"}";
    }
    $res = json_decode($response);
    return $res;
}

function Responese($command, $data, $message, $status, $code, $token)
{

    if (empty($data)) {
        $response = "{\"command\":\"$command\",\"data\":\"[]\",\"message\":\"$message\",\"status\":\"$status\",\"code\":\"$code\",\"token\":\"$token\"}";
    } else {
        if(is_array($data)){
            $json = json_encode($data);
            $response = "{\"command\":\"$command\",\"data\":$json,\"message\":\"$message\",\"status\":\"$status\",\"code\":\"$code\",\"token\":\"$token\"}";
        }else{
            $response = "{\"command\":\"$command\",\"data\":[$data],\"message\":\"$message\",\"status\":\"$status\",\"code\":\"$code\",\"token\":\"$token\"}";
        }
    }
    return $response;
}

function Initialization()
{
    $token = isset(getallheaders()['token']) ? getallheaders()['token'] : die(json_encode(array("status" => "There is no authorization")));

    if ((isset($token) and checkToken($token))) {
        $svcCheck = new StateCheck();
        $tokenuid = -1;
        if (isset($token)) {
            $tokenuid = checkToken($token);
        }

        if ($tokenuid > -1) {
            $user_id = $tokenuid;
            $_SESSION["uid"] = $user_id;
            $_SESSION['pass'] = authorizeToken($token);

            $_SESSION["token"] = 0;
            $_SESSION["socket"] = 0;
            $_SESSION["command"] = 0;

            $_SESSION['endpoint'] = $svcCheck->createEndPoint($user_id);
            /// $_SESSION['timezone'] = timeZoneToken($token);
            $_SESSION['timezone'] = 7;
            $alldetails = allDetailsToken($token);
            $_SESSION['unit_speed'] = $alldetails->us;
            $_SESSION['unit_distance'] = $alldetails->ud;
            $_SESSION['unit_fuel'] = $alldetails->uf;
            $_SESSION['unit_temperature'] = $alldetails->ut;
            $_SESSION['lang'] = $alldetails->lang;
            $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

        } else {
            echo json_encode(array('status' => 0, 'message' => 'you have no Authorize'));
            die();
        }

    } else {
        echo json_encode(array('status' => 0, 'message' => 'No Authorize'));
        die();
    }
}

function InitializeSocket($msg)
{
    $token = isset($msg['token']) ? $msg['token'] : "";
    if ((!empty($token) and checkToken($token))) {
        // $svcCheck = new StateCheck();
        $tokenuid = -1;
        if (!empty($token)) {
            $tokenuid = checkToken($token);
        }

        if ($tokenuid > -1) {
            $user_id = $tokenuid;
            $_SESSION["uid"] = $user_id;
            $_SESSION["token"] = $msg['token'];
            $_SESSION["socket"] = 1;
            $_SESSION["command"] = $msg['command'];

            $_SESSION['pass'] = authorizeToken($token);
            // $_SESSION['endpoint'] = $svcCheck->createEndPoint($user_id);
            /// $_SESSION['timezone'] = timeZoneToken($token);
            $_SESSION['timezone'] = 7;
            $alldetails = allDetailsToken($token);
            $_SESSION['unit_speed'] = $alldetails->us;
            $_SESSION['unit_distance'] = $alldetails->ud;
            $_SESSION['unit_fuel'] = $alldetails->uf;
            $_SESSION['unit_temperature'] = $alldetails->ut;
            $_SESSION['lang'] = $alldetails->lang;
            $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
            return 1;
        } else {
            return Responese("", "", "No Authorize", 0, "", "");
        }
    } else {
        return Responese("", "", "you have no Authorize", 0, "", "");
    }
}

function GetMethod()
{
    return isset(getallheaders()['m']) ? getallheaders()['m'] : die(json_encode(array("status" => "wrong method")));
}
function getCommandSocket($msg)
{
    $command = isset($msg['command']) ? $msg['command'] : "";
    return $command;
}
function getMethodSocket($msg)
{
    $m = isset($msg['m']) ? $msg['m'] : "";
    return $m;
}
function getData($msg){
	$data = isset($msg['data'][0])?$msg['data'][0]:"";
	return $data;
}
{

}
function IsMyself()
{
    return isset(getallheaders()['view']) ? true : false;
}
function IsMyselfSocket($msg)
{
    return isset($msg['view'])? true:false;
}
function newUserPermission()
{
    return "declare @code int
				begin try
					begin tran
					declare @purview    int
					exec @purview = dbo.p_user_have_purview 1, 3300, 'S'
					if @purview > 0
					begin
						declare @pur_n varchar(256),
								@i int,
								@pid int,
								@p varchar(20),
								@one_pur varchar(256),
								@cmds_n varchar(1024),
								@one_cmd varchar(20)

						set @code = -1
						set @pur_n = '3000:;3200:AMDR;3300:;3400:S;3500:;1000:;1090:M;1300:AMD;1700:AMD;';
						delete from dbo.cfg_user_purview where user_id  in  (select Distinct user_id from dbo.sys_user where user_id >1);
						set @code = -2

						while len(@pur_n) > 0
						begin
								set @i = charindex(';',@pur_n)
								if(@i > 0)
								begin
									declare @z int
									set @one_pur = substring(@pur_n, 1, @i - 1)
									set @z = charindex(':', @one_pur)
									if (@z > 0)
									begin
										set @pid = convert(int,substring(@one_pur, 1, @z - 1))
										set @p = substring(@one_pur, @z + 1, len(@one_pur) -@z);
										insert into dbo.cfg_user_purview (user_id,purview_id,purview)  (select  user_id, @pid,@p from dbo.sys_user where user_id >1);
									end
									set @pur_n = substring (@pur_n , @i + 1 , len(@pur_n) - @i)
								end
								else
									break
						end

						set @cmds_n = '1;2;3;4;5;6;7;8;9;10;11;12;13;14;15;16;17;18;19;20;21;22;23;25;26;28;29;30;31;32;33;34;37;38;39;40;41;42;47;48;56;57;58;66;67;85;86;87;88;90;114;234;243;251;252;272;273;276;293;609;653;659;665;666;667;668;669;1027;1184;1185;1186;1187;1188;1189;1190;1191;1192;1193;1194;'
						delete from cfg_user_command where user_id in  (select Distinct user_id from dbo.sys_user where user_id >1);
						set @code = -3

						while len(@cmds_n) > 0
						begin
								set @i = charindex(';',@cmds_n)
								if(@i > 0)
								begin
									set @one_cmd = convert(int,substring(@cmds_n, 1, @i - 1))
									insert into cfg_user_command (user_id, command_id)   (select  user_id, @one_cmd from dbo.sys_user where user_id >1);
									set @cmds_n = substring (@cmds_n , @i + 1 , len(@cmds_n) - @i)
								end
								else
									break
						end

						set @code = 0
					end
					else
						set @code = -20
							commit tran
						end try
						begin catch
							rollback tran
						end catch
				select @code as errcode";
}
