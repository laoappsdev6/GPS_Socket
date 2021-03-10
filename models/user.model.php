<?php

class UserModel
{
    public $usrid_;
    public $uname_;
    public $login_;
    public $upass_;
    public $email_;
    public $rtime_;
    public $rmail_;
    public $mtype_;
    public $valid_;
    public $group_;
    public $uphone_;
    public $olimit_;
    public $self_;

    public $socket;
    public $token;
    public $command;

    public function __construct($object, $needEmpty = false)
    {

        $this->socket = $_SESSION["socket"];
        $this->token = $_SESSION["token"];
        $this->command = $_SESSION["command"];
        if (!$needEmpty) {
            if (!$object) {
                if ($this->socket <> 1) {
                    PrintJSON("", "data is empty", 0);
                    die();
                }
            }
        };
        foreach ($object as $property => $value) {
            if (property_exists('UserModel', $property)) {
                $this->$property = $value;
            }
        }
    }
    public function checkAllProperties()
    {
        $result = array();
        foreach ($this as $property => $value) {
            $check = $this->validate($property);
            @sizeof($check)>0?$result[]=$check:'';
        }
        return $result;
    }
    public function checkId()
    {
        $db = new db_mssql();
        $sql = "select * from sys_user where user_id='$this->usrid_' ";
        $name = $db->query($sql);
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" user ID: " . $this->usrid_ . " is not available!", 0);
            } else {
                PrintJSON("", " user ID: " . $this->usrid_ . " is not available!", 0);
                die();
            }
        }
    }
    public function validate($p)
    {
        switch ($p) {
            case 'uname_':
                return $this->validateUsername();
                break;
            case 'login_':
                return $this->validateLoginname();
                break;
            case 'upass_':
                return $this->validatePassword();
                break;
            case 'valid_':
                return $this->valaidateValid();
                break;
            case 'group_':
                return $this->validateGroup();
                break;
            case 'uphone_':
                return $this->validatePhonenumber();
                break;
            case 'olimit_':
                return $this->validateObjectlimit();
                break;
        }
    }
    public function validateUsername()
    {
        if (strlen($this->uname_) < 3) {
            if ($this->socket == 1) {
               return JsonValidate("Username is short", 0);
            } else {
                PrintJSON("", "Username is short", 0);
                die();
            }
        }
    }
    public function validateLoginname()
    {
        $db = new db_mssql();
        $sql = "select * from sys_user where login_name='$this->login_' and user_id!='$this->usrid_' ";
        $name = $db->query($sql);
        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate("login name: " . $this->login_ . " already exit!", 0);
            } else {
                PrintJSON("", " login name: " . $this->login_ . " already exit!", 0);
                die();
            }
        } 
        if (strlen($this->login_) < 2) {
            if ($this->socket = 1) {
               return JsonValidate("login name is short", 0);
            } else {
                PrintJSON("", "login name is short", 0);
                die();
            }
        }
    }
    public function validatePassword()
    {
        if (strlen($this->upass_) < 6) {
            if ($this->socket == 1) {
                return JsonValidate("password must be then 6 digists", 0);
            } else {
                PrintJSON("", "password must be then 6 digists", 0);
                die();
            }
        }
    }
    public function valaidateValid()
    {
        if (!is_numeric($this->valid_)) {
            if ($this->socket == 1) {
                return JsonValidate("Valid is number only", 0);
            } else {
                PrintJSON("", "Valid is number only", 0);
                die();
            }
        }
    }
    public function validateGroup()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_group where group_id IN($this->group_)";
        $name = $db->query($sql);
     
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" group ID: " . $this->group_ . " is not available!", 0);
            } else {
                PrintJSON("", " group ID: " . $this->group_ . " is not available!", 0);
                die();
            }
        }
    }
    public function validatePhonenumber()
    {
        $number = preg_match('@[0-9]@', $this->uphone_);
        if (!$number || strlen($this->uphone_) < 10) {
            if ($this->socket == 1) {
                return JsonValidate("Phonenumber must be 10 digists and number only", 0);
            } else {
                PrintJSON("", "Phonenumber must be 10 digists and number only", 0);
                die();
            }
        }
    }
    public function validateObjectlimit()
    {
        if (!is_numeric($this->olimit_)) {
            if ($this->socket == 1) {
                return JsonValidate("Object limit is number only", 0);
            } else {
                PrintJSON("", "Object limit is number only", 0);
                die();
            }
        }
    }

}
