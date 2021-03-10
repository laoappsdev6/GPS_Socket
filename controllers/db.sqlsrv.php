<?php

require_once('db.class.php');

class db_mssql extends db {

    protected function do_connect() {
        if (!function_exists('sqlsrv_connect')) {
            echo "hello";
        $this->write_log('PHP no support SQLSRV libary!');
            return false;
        }
        $connectionInfo = array("UID" => $this->db_user, "PWD" => $this->db_pwd, "Database" => $this->db_dbms, "CharacterSet" => "UTF-8");
        if (!$this->conn = sqlsrv_connect($this->db_host, $connectionInfo)) {
            die( print_r( sqlsrv_errors(), true));
			$this->write_log('connect error.');
            return false;
        } else {
            $this->write_log('--sqlsrv_connect called.');
            return true;
        }
    }
    protected function do_close() {
        if ($this->conn) {
            sqlsrv_close($this->conn);
        }
    }

    protected function do_exec($sql) {
        $var = false;
        $res = sqlsrv_prepare($this->conn, $sql);
        if (sqlsrv_execute($res)) {
            $var = true;
        } else {
            $this->write_log('--sql error:' . $sql . "\n");
            $var = false;
        }
        sqlsrv_free_stmt($res);
        return $var;
    }

    protected function do_query($sql) {
        $var = null;
        $res = sqlsrv_query($this->conn, $sql);
        if ($res === false) {
            $this->write_log('--sql error:' . $sql . "\n");
            return $var;
        } else {
            while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                $var[] = $row;
            }
        }
        sqlsrv_free_stmt($res);
        return $var;
    }
    protected function do_query_last_ds($sql) {
        $var = null;
        $res = sqlsrv_query($this->conn, $sql);
        //print_r($res);
        if ($res === false) {
            $this->write_log('--sql error:' . $sql . "\n");
            return $var;
        } else {
            while (sqlsrv_has_rows($res) !== TRUE) {
                sqlsrv_next_result($res);
            }
            while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
                $var[] = $row;
            }
        }
        sqlsrv_free_stmt($res);
        return $var;
    }

}