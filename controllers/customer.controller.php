<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
require_once __DIR__ . "./../services/services.php";
require_once __DIR__ . "./../services/common.inc.php";
require_once __DIR__ . "./svc.class.php";
require_once __DIR__ . "./db.class.php";
require_once __DIR__ . "./db.sqlsrv.php";
class CustomerController
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
    public function addCustomer($cu)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION["uid"];
            $subsql = "
                                declare @purview    int
                                exec @purview = dbo.p_user_have_purview $user_id, 2100, 'A'
                                if @purview > 0
                                begin
                                    set @code = -1
                                    insert into cfg_customer (short_name, full_name, phone, remark, user_id)
                                        values (N'$cu->name', N'$cu->fname', N'$cu->phone', N'$cu->remark', $user_id)
                                    set @id = @@identity
                                    set @code = 0
                                end
                                else
                                    set @code = -20";
            //    echo $subsql;die();
            $sql = "declare @code int, @id int
                                    begin
                                        $subsql
                                    end
                                    select @code as errcode, @id as customer_id";

            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];
            $customer_id = $data[0]['customer_id'];
            if (!is_null($error_code) && $error_code == 0) {
                $json = array("customer_id" => "$customer_id", "message" => "add customer_id Ok", "status" => "1");
                if ($this->socket == 1) {
                    return Responese($this->command, $json, "add customer ok", 1, "", "");
                } else {
                    echo json_encode($json);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "add customer fail! error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "add customer fail! error: " . $error_code, 0);
                    die();
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "add customer fail! error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "add customer fail! error: " . $e, 0);
            }
        }
    }
    public function updateCustomer($cu)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION["uid"];
            $subsql = "
            declare @purview    int,
                    @have       int
            exec @purview = dbo.p_user_have_purview $user_id, 2100, 'M'
            exec @have = dbo.p_user_have_customer $user_id, $cu->custid
            if @purview > 0 and @have > 0
            begin
                set @code = -1
                update cfg_customer set short_name = N'$cu->name', full_name = N'$cu->fname', phone = N'$cu->phone', remark = N'$cu->remark' where customer_id = $cu->custid
                set @code = 0
            end
            else
                set @code = -20
            ";
            $sql = "declare @code int
                                begin
                                    $subsql
                                end
                                select @code as errcode";

            $data = $db->queryLastDS($sql);
            $error_code = $data[0]['errcode'];

            if (!is_null($error_code) && $error_code == 0) {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update Customer Ok ", 1, "", "");
                } else {
                    PrintJSON("", "update Customer Ok ", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "", "update fail error:" . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "update fail error:" . $error_code, 0);
                    die();
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "", "update fail error:" . $e, 0, "", "");
            } else {
                PrintJSON("", "update fail error:" . $e, 0);
            }
        }

    }
    public function deleteCustomer($cu)
    {
        try {
            $db = new db_mssql();
            $user_id = $_SESSION["uid"];
            $subsql = "
        declare @purview    int,
                @have       int
        exec @purview = dbo.p_user_have_purview $user_id, 2100, 'D'
        exec @have = dbo.p_user_have_customer $user_id, $cu->custid
        if @purview > 0 and @have > 0
        begin
            set @code = -1
            if exists(select object_id from cfg_object where customer_id = $cu->custid)
            begin
                set @code = -2;
            end
            else
            begin
                delete from cfg_customer where customer_id =$cu->custid
                set @code = 0
            end
        end
        else
            set @code = -20";
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
                    return Responese($this->command, "", "customer id: " . $cu->custid . " delete Ok!", 0, "", "");
                } else {
                    PrintJSON("", "customer id: " . $cu->custid . " delete Ok!", 1);
                }
            } else {
                if ($this->socket == 1) {
                    return Responese($this->command, "",  "delete fail error: " . $error_code, 0, "", "");
                } else {
                    PrintJSON("", "delete fail error: " . $error_code, 0);
                }
            }
        } catch (Exception $e) {
            if ($this->socket == 1) {
                return Responese($this->command, "",  "delete fail error: " . $e, 0, "", "");
            } else {
                PrintJSON("", "delete fail error: " . $e, 0);
            }
        }

    }
    public function CustomerList($cu)
    {
        try {

            $db = new db_mssql();
            $user_id = $_SESSION["uid"];
            if ((isset($cu->page) && $cu->page != "") && (isset($cu->limit) && $cu->limit != "")) {

                $offset = (($cu->page - 1) * $cu->limit);

                $sql = "select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p,dbo.cfg_customer where
            p.user_id = $user_id and p.purview_id = 1000 and customer_id in
           (select customer_id from dbo.cfg_object where group_id in ( select group_id from dbo.fn_group4user($user_id)))
           ";
                if (isset($cu->keyword) && $cu->keyword != "") {
                    $sql .= "and (
                        customer_id like '%$cu->keyword%' or
                        short_name like '%$cu->keyword%' or
                        full_name like '%$cu->keyword%' or
                        phone like '%$cu->keyword%' or
                        remark like '%$cu->keyword%'
                          )";
                }
                $sql .= "
           union
           select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p, dbo.cfg_customer c where p.user_id = $user_id and p.purview_id = 1000 and c.user_id = $user_id
                ";
                if (isset($cu->keyword) && $cu->keyword != "") {
                    $sql .= "and (
                                customer_id like '%$cu->keyword%' or
                                short_name like '%$cu->keyword%' or
                                full_name like '%$cu->keyword%' or
                                phone like '%$cu->keyword%' or
                                remark like '%$cu->keyword%'
                                  )";
                }
                $sql .= "
           union
           select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p, dbo.cfg_customer c where p.user_id = $user_id and p.purview_id = 1000 and c.user_id in (select user_id from dbo.fn_user_tree($user_id))
           ";
                if (isset($cu->keyword) && $cu->keyword != "") {
                    $sql .= "and (
                        customer_id like '%$cu->keyword%' or
                        short_name like '%$cu->keyword%' or
                        full_name like '%$cu->keyword%' or
                        phone like '%$cu->keyword%' or
                        remark like '%$cu->keyword%'
                          )";
                }

                $sql_page = "order by customer_id desc offset $offset rows fetch next $cu->limit rows only  ";
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
                $total_page = ceil($number_count / $cu->limit);
                $list3 = json_encode($total_page);
                $json = "{  \"Data\":$list1,
                        \"Page\":$cu->page,
                        \"Pagetotal\":$list3,
                        \"Datatotal\":$number_count
                    }";
                    if ($this->socket == 1) {
                        return Responese($this->command, $json,  "data list page of customer", 1, "", "");
                    } else {
                        echo $json;
                    }
            } else {
                if (isset($cu->custid)) {
                    $custjid = $cu->custid;
                    //query one customer
                    $sql = "select customer_id id,short_name name,full_name fname,phone p,remark r
                        from cfg_customer where customer_id = $custjid";
                    $custlist = $db->query($sql);
                    $json = array2json($custlist);
                    if ($this->socket == 1) {
                        return Responese($this->command, $json,  "data list one of customer ", 1, "", "");
                    } else {
                        echo $json;
                    }
                } else {
                    //query customer list
                    $sql = "select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p,dbo.cfg_customer where
                        p.user_id = $user_id and p.purview_id = 1000 and customer_id in
                       (select customer_id from dbo.cfg_object where group_id in ( select group_id from dbo.fn_group4user($user_id)))
                       union
                       select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p, dbo.cfg_customer c where p.user_id = $user_id and p.purview_id = 1000 and c.user_id = $user_id
                       union
                       select customer_id id,short_name name,full_name fname,phone p,remark r from cfg_user_purview p, dbo.cfg_customer c where p.user_id = $user_id and p.purview_id = 1000 and c.user_id in (select user_id from dbo.fn_user_tree($user_id))
                       ";
                    $custlist = $db->query($sql);

                    $sql_pur = "select purview_id pid, isnull(purview,'') p from cfg_user_purview where user_id = $user_id and purview_id = 2100";
                    $upurview = $db->query($sql_pur);

                    $list = array2json($custlist);
                    $pr = array2json($upurview);
                    $json = "{\"list\": $list, \"pur\": $pr}";
                    if ($this->socket == 1) {
                        return Responese($this->command, $json,  "data list all of customer ", 1, "", "");
                    } else {
                        echo $json;
                    }
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }

    }

}
