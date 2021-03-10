<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";

class PlaceController
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

    function addPlace($pl)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $unit_speed = $_SESSION['unit_speed'];
            // if ($unit_speed == 1 && $row['ins'] >= 0) {
            //     //速度转为km/h
            //     $ins = round($ins * 1.609344, 0);
            // }

            $subsql = "declare @purview    int
                       exec @purview = dbo.p_user_have_purview $user_id, 1700, 'A'							   
                       if @purview > 0
                       begin
                         set @code = -1
                         declare @total int
                         select @total = count(*) from cfg_place where user_id = $user_id;
                       
                         if @total <= 1000
                         begin
                           insert into cfg_place (USER_ID, area_name, area_type, area_pts, aera_color, aera_zoom, enable_speed_limit, inside_speed_limit) values 
                           ($user_id, N'$pl->aname', $pl->atype, '$pl->apts', '$pl->acolor', $pl->zoom, $pl->es, $pl->ins); 
                           set @max_id = @@identity; 
                           set @code = 0
                         end else
                         begin
                           set @code = -2
                         end
                       end
                       else
                         set @code = -20";
                       //  echo $subsql;die();
            $sql = "declare @code int, @max_id int
                         begin try
                             begin tran
                             $subsql
                             commit tran   
                         end try
                         begin catch
                             rollback tran
                         end catch
     
                         select @code as errcode, @max_id as zid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add place ok!", 1, "", "");
                } else {
                    PrintJSON("", "add place Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add place fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "add place fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "add place fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "add place fail error: " . $e, 0);
            }
        }
    }
    function updatePlace($pl)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $unit_speed = $_SESSION['unit_speed'];
            // if ($unit_speed == 1 && $row['ins'] >= 0) {
            //     //速度转为km/h
            //     $ins = round($ins * 1.609344, 0);
            // }

            $subsql = "declare @purview    int,
                               @have       int
                       exec @purview = dbo.p_user_have_purview $user_id, 1700, 'M'
                       exec @have = dbo.p_user_have_place $user_id, $pl->zid
                       if @purview > 0 and @have > 0
                       begin
                         set @code = -1 
                         update cfg_place set area_name = N'$pl->aname', area_type = $pl->atype, area_pts = '$pl->apts', aera_color = '$pl->acolor', aera_zoom = $pl->zoom, enable_speed_limit = $pl->es, inside_speed_limit = $pl->ins where place_id = $pl->zid 
                         set @code = 0
                       end
                       else
                         set @code = -20";
            $sql = "declare @code int, @max_id int
                                         begin try
                                             begin tran
                                             $subsql
                                             commit tran   
                                         end try
                                         begin catch
                                             rollback tran
                                         end catch
                     
                                         select @code as errcode, @max_id as zid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update place Ok! ", 1, "", "");
                } else {
                    PrintJSON("", "update place Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update place fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "update place fail error: " . $error_code, 0);
                }
            }

        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "update place fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "update place fail error: " . $e, 0);
            }
        }
    }
    function deletePlace($pl)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $subsql = "declare @purview    int,
                       @have       int
               exec @purview = dbo.p_user_have_purview $user_id, 1700, 'D'
               exec @have = dbo.p_user_have_place $user_id, $pl->zid
               if @purview > 0 and @have > 0
               begin
                  set @code = -1 
                  delete from cfg_place where place_id = $pl->zid 
                  set @code = 0
               end
               else
                 set @code = -20";
            $sql = "declare @code int, @max_id int
                                 begin try
                                     begin tran
                                     $subsql
                                     commit tran   
                                 end try
                                 begin catch
                                     rollback tran
                                 end catch
             
                                 select @code as errcode, @max_id as zid";
            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            if ($error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "",  "ID: " . $pl->zid . "  deleted ", 0, "", "");
                } else {
                    PrintJSON("", "ID: " . $pl->zid . "  deleted ", 1);
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
    function placeList($pl)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $unit_speed = $_SESSION['unit_speed'];
            if (isset($pl->zid)) {
                $zid = $pl->zid;
                //query one place
                $sql = "select area_name an, area_type at, area_pts ap, aera_color ac, aera_zoom zoom, isnull(enable_speed_limit, 0) es, isnull(inside_speed_limit, '') ins
                        from cfg_place where place_id = $zid";
                $geolist = $db->query($sql);
                if (!empty($geolist)) {
                    foreach ($geolist as $row) {
                        //speed unit
                        if ($unit_speed == 1 && strlen($row['ins']) > 0 && $row['ins'] >= 0) {
                            //mph(英里/小时)
                            $row['ins'] = round($row['ins'] * 0.6213712, 0);
                        }
                        $output[] = $row;
                    }
                }
                $json = array2json($output);
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data one of place by zid", 1, "", "");
                } else {
                    echo $json;
                }
            } else {
                //query place list
                if (isset($pl->full)) {
                    $sql = "select place_id zid, area_name an, area_type at, area_pts ap, aera_color ac, aera_zoom zoom, enable_speed_limit es, inside_speed_limit ins
                            from cfg_user_purview p, cfg_place pl where p.user_id = $user_id and p.purview_id = 1000 and pl.place_id in (select place_id from dbo.fn_place4user($user_id))";
                } else {
                    $sql = "select place_id zid, area_name an, area_type at 
                            from cfg_user_purview p, cfg_place pl where p.user_id = $user_id and p.purview_id = 1000 and pl.place_id in (select place_id from dbo.fn_place4user($user_id))";
                }
                $geolist = $db->query($sql);
        
                $sql_pur = "select purview_id pid, isnull(purview,'') p from cfg_user_purview where user_id = $user_id and purview_id = 1700";
                $upurview = $db->query($sql_pur);
        
                $json = array2json($geolist);
                $pr = array2json($upurview);
                $json = "{\"self\":$user_id, \"list\":$json, \"pur\": $pr}";
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "data all of place", 1, "", "");
                } else {
                    echo $json;
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
}

?>