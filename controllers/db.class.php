<?php

class db {

    var $last_sql;
    var $conn;
    var $log;
    var $db_host;
    var $db_dbms;
    var $db_user;
    var $db_pwd;
    var $error_code = 0;
    private $is_debug = false;

    function write_log($text) {
        if ($this->is_debug) {
            $this->log .= $text . '<br />';
        }
    }

    public function __construct( $debug=true) {
        $this->is_debug = $debug;
        $this->write_log('initialize db() called.');
        $host = 'localhost';//127.0.0.1'
        $dbms = 'ANB-GPSDB';
        $user = 'sa';
        $pwd = '123456';
        $this->db_host = $host;
        $this->db_dbms = $dbms;
        $this->db_user = $user;
        $this->db_pwd = $pwd;
    }

    public function __destruct() {
        $this->do_close();
    }

    protected function connect() {
        $this->write_log('connect() called.');
        return $this->do_connect();
    }

    public function exec($sql) {
        $this->last_sql = $sql;
        $this->write_log('exec() called.');
        $this->write_log('SQL: ' . $sql);
        if (!$this->conn) {
            if (!$this->connect()) {
                return false;
            }
        }
        return $this->do_exec($sql);
    }

    public function query($sql) {
        $this->last_sql = $sql;
        $this->write_log('query() called.');
        if (!$this->conn) {
            try {
                if (!$this->connect()) {
                    $this->error_code = 1;//connect fail
                    return null;
                }
            } catch (Exception $e) {
                $this->error_code = 2;//exception
                return null;
            }
        }
        return $this->do_query($sql);
    }
    
    public function queryLastDS($sql) {
        $this->last_sql = $sql;
        $this->write_log('query() called.');
        if (!$this->conn) {
            try {
                if (!$this->connect()) {
                    $this->error_code = 1;//connect fail
                    return null;
                }
            } catch (Exception $e) {
                $this->error_code = 2;//exception
                return null;
            }
        }
        return $this->do_query_last_ds($sql);
    }
    

    public static function table($var) {
        if (!$var) 
            return false;
        echo '<style>table.dump { font-family:Arial; font-size:8pt; }</style>';
        echo '<table class="dump" border="1" cellpadding="1" cellspacing="0">' . "\n";
        echo '<tr>';
        echo '<th>#</th>';
        foreach ($var[0] as $key => $val) {
            echo '<th><b>';
            echo $key;
            echo '</b></th>';
        }
        echo '</tr>' . "\n";
        $row_cnt = 0;
        foreach ($var as $row) {
            $row_cnt++;
            echo '<tr align="center">';
            echo '<td>' . $row_cnt . '</td>';
            foreach ($row as $val) {
                echo '<td>';
                echo $val;
                echo '</td>';
            }
            echo '</tr>' . "\n";
        }
        echo '</table>' . "\n";
    }

    public static function dump($var, $echo=true, $label=null, $strict=true) {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }
}

?>