<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";

class GroupServiceController
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
    public function addGroup($get)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $sql = "declare @owner_id     int,
                        @group_parent int;
                        select @owner_id = owner_id from sys_user where user_id = $user_id;
                        select @group_parent = group_parent from cfg_group where group_id = $get->group_parent;
                        select @owner_id as owner_id, @group_parent as group_parent;";

            $data = $db->queryLastDS($sql);
            $owner_id = $data[0]['owner_id'];
            $group_parent = $data[0]['group_parent'];

            if ($owner_id != 0 && $group_parent == 0) {
                echo "{'status': 'no parent group'}";
                return;
            }

            $subsql = "
                        declare @purview    int
                        exec @purview = dbo.p_user_have_purview $user_id, 3400, 'S'
                        if @purview > 0
                        begin
                            set @code = -1
                            insert into cfg_group (group_name, group_parent) values (N'$get->group_name', $get->group_parent)
                            set @code = -2

                            insert into cfg_user_group
                            select $user_id, group_id from cfg_group where group_name = N'$get->group_name'
                            set @code = 0
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
            $error_code = $data[0]['errcode'];

            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add group Ok! ", 1, "", "");
                } else {
                    PrintJSON("", "add group Ok! ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add group fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "add group fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "add group fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "add group fail error: " . $e, 0);
            }
        }
    }
    public function deleteGroup($gid)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];
            $sql = "
                declare @purview    int,
                        @have       int
                exec @purview = dbo.p_user_have_purview $user_id, 3400, 'S'
                exec @have = dbo.p_user_have_group $user_id, $gid
                if @purview > 0 and @have > 0
                begin
                    exec p_delete_group $gid
                end";
            $data = $db->query($sql);
            if (!is_null($data)) {
                /*不考虑更新memcache,账户重新登陆后会更新*/
                $json = array2json($data);
                if ($this->socket == 1) {
                    return Responese($this->command, "", "ID: " . $gid . " deleted", 1, "", "");
                } else {
                    PrintJSON("", "ID: " . $gid . " deleted", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "delete group fail !!!!", 0, "", "");
                } else {
                    PrintJSON("", "delete group fail !!!!", 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "delete group fail !!!!", 0, "", "");
            } else {
                PrintJSON("", "delete group fail !!!!", 0);
            }
        }
    }
    public function tree($table, $etype, $p_id)
    {
        $tree = array();
        foreach ($table as $row) {
            if ($row['parent'] == $p_id) {
                $child = $this->tree($table, $etype, $row['id']);
                $row['value'] = $row['id'];
                $row['id'] = 'id_' . $row['id'];
                $row['complete'] = true;
                $row['isexpand'] = true;
                if ($etype == 1) {
                    //edit
                    $row['showcheck'] = $row['showcheck'] != null;
                    $row['checkstate'] = $row['checkstate'] == null ? 0 : 1;
                } else {
                    //view
                    $row['showcheck'] = ($row['showcheck'] != null) && ($row['checkstate'] != null);
                    $row['checkstate'] = $row['showcheck'] == null ? 0 : 1;
                }

                if ($child) {
                    $row['hasChildren'] = true;
                    $row['ChildNodes'] = $child;
                } else {
                    $row['hasChildren'] = false;
                }
                unset($row['parent']);
                $tree[] = $row;
            }
        }
        return $tree;
    }

    public function isTopGroup($table, $group)
    {
        foreach ($table as $row) {
            if ($row['id'] == $group['parent']) {
                return false;
            }
        }
        return true;
    }

    public function Grouplist($get)
    {
        try {
            $user_id = (int) $_SESSION['uid'];
            $subuser = (int) @$get->usrid;
            if ($user_id == $subuser) {
                $subuser = 0;
            }
            $etype = (int) @$get->etype;
            $gname = @$get->gname == "" ? "%%" : "%" . $get->gname . "%";

            if ($etype == 1) {
                $eshow = $user_id;
            } else {
                $eshow = $subuser;
            }
            $db = new db_mssql();
            $user_id = $_SESSION['uid'];

            $sql = "select t1.*, t2.showcheck, t3.checkstate from (
				select group_id id, group_name text, group_parent parent from cfg_group
				where group_id in (
					select group_id from dbo.fn_group4user($user_id)
				) and group_name like '$gname'
			) t1
			left join
			(
				select group_id showcheck from dbo.fn_group4user($eshow)
			) t2 on t1.id = t2.showcheck
			left join
			(
				select group_id checkstate from dbo.fn_group4user($subuser)
			) t3 on t1.id = t3.checkstate
			order by t1.parent, t1.text";

            $data = $db->query($sql);

            if (!empty($data)) {
                foreach ($data as &$row) {
                    if ($this->isTopGroup($data, $row)) {
                        $row['parent'] = 0;
                    }
                }
                $tree = $this->tree($data, $etype, $data[0]['parent']);
            }
            $data = array('id' => 'id_0',
                // 'text' => $TEXT['info-mygroup'],
                'test' => 'My Group',
                'complete' => true,
                'showcheck' => $subuser != 0 && $etype == 1,
                'hasChildren' => $tree != null,
                'checkstate' => 0,
                'isexpand' => true,
                'value' => '0',
                'ChildNodes' => $tree,
            );
            $json = array2json($data);
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data list tree of group", 1, "", "");
            } else {
                echo '[' . $json . ']';
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function getGroup()
    {
        try {

            $db = new db_mssql();
            $sql = "select * from cfg_group";
            $data = $db->query($sql);
            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data list of group", 1, "", "");
            } else {
                echo $json;
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function groupCustomer($cust)
    {
        try {
            // print_r($cust);
            $db = new db_mssql();
            $sql = "select * from cfg_group where group_id IN(";

            for ($a = 0; $a < sizeof($cust); $a++) {
                $group_id = $cust[$a]['group_id'];
                if ($a == sizeof($cust) - 1) {
                    $sql .= "$group_id)";
                } else {
                    $sql .= "$group_id,";
                }
            }
            $data = $db->query($sql);
            $list = json_encode($data);
            $json = "{\"Data\":$list}";
            if ($this->socket == 1) {
                return Responese($this->command, $json, "data list group by customer", 1, "", "");
            } else {
                echo $json;
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }
    public function GroupTree()
    {
        try {

            $db = new db_mssql();
            $sql = "select group_parent,group_id,group_name from cfg_group order by group_parent asc, group_id asc";
            $data = $db->query($sql);
            $list = $this->createGrouptree($data);
            $json = "{\"Data\":$list}";
            echo $json;
        } catch (Exception $e) {
            print_r($e);
        }
    }

    public function createGrouptree($data)
    {
        $itemsByReference = array();

        foreach ($data as $key => &$item) {
            // print_r($item);sss
            $itemsByReference[$item['group_id']] = &$item;
            // Children array:
            $itemsByReference[$item['group_id']]['children'] = array();
            // Empty data class (so that json_encode adds "data: {}" )
            // $itemsByReference[$item['group_id']]['data'] = new StdClass();
        }

        // Set items as children of the relevant parent item.
        foreach ($data as $key => &$item) {
            if ($item['group_parent'] && isset($itemsByReference[$item['group_parent']])) {
                $itemsByReference[$item['group_parent']]['children'][] = &$item;
            }
        }

        // Remove items that were added to parents elsewhere:
        foreach ($data as $key => &$item) {
            if ($item['group_id'] && isset($itemsByReference[$item['group_parent']])) {
                unset($data[$key]);
            }

        }
        // Encode:
        $json = json_encode($data);
        return $json;
    }
}
