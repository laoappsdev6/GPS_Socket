<?php

class DriverModel
{
    public $jno;
    public $name;
    public $oid;
    public $sex;
    public $ip;
    public $id;
    public $l;
    public $isd;
    public $exd;
    public $tel;
    public $rfid;
    public $co;
    public $addr;
    public $r;
    public $p;

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
                if ($this->socket != 1) {
                    PrintJSON("", "data is empty", 0);
                    die();
                }
            }
        };
        foreach ($object as $property => $value) {
            if (property_exists('DriverModel', $property)) {
                $this->$property = $value;
            }
        }
    }

    public function checkAllProperties()
    {
        $result = array();
        foreach ($this as $property => $value) {
            $check = $this->validate($property);
            @sizeof($check) > 0 ? $result[] = $check : '';
        }
        return $result;
    }

    public function validate($p)
    {
        switch ($p) {
            case 'name':
                return $this->validateNmae();
                break;
            case 'sex':
                return $this->validateSex();
                break;
            case 'ip':
                return $this->validateIp();
                break;
            case 'id':
                return $this->validateId();
                break;
            case 'l':
                return $this->validateL();
                break;
            case 'isd':
                return $this->validateIsd();
                break;
            case 'exd':
                return $this->validateExd();
                break;
            case 'tel':
                return $this->validateTel();
                break;
            case 'rfid':
                return $this->validateRfid();
                break;
            case 'addr':
                return $this->validateAddr();
                break;
        }
    }
    public function checkId()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_driver where job_number='$this->jno'";
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate("Job number: " . $this->jno . " already exist", 0);
            } else {
                PrintJSON("", "Job number: " . $this->jno . " already exist", 0);
                die();
            }
        }
    }
    public function validateNmae()
    {
        if (strlen($this->name) < 3) {
            if ($this->socket == 1) {
                return JsonValidate("Driver name is short ", 0);
            } else {
                PrintJSON("", "Driver name is short ", 0);
                die();
            }
        }
    }
    public function validateSex()
    {
        if (!is_numeric($this->sex)) {
            if ($this->socket == 1) {
                return JsonValidate("Sex is number only", 0);
            } else {
                PrintJSON("", "Sex is number only", 0);
                die();
            }
        }
    }
    public function validateIp()
    {
        if ($this->ip == "") {
            if ($this->socket == 1) {
                return JsonValidate("Ip is empty", 0);
            } else {
                PrintJSON("", "Ip is empty", 0);
                die();
            }
        }
    }
    public function validateId()
    {
        if ($this->id == "") {
            if ($this->socket == 1) {
                return JsonValidate("Id is not valid", 0);
            } else {
                PrintJSON("", "Id is not valid", 0);
                die();
            }
        }
    }
    public function validateL()
    {
        if ($this->l == "") {
            if ($this->socket == 1) {
                return JsonValidate("License is not valid", 0);
            } else {
                PrintJSON("", "License is not valid", 0);
                die();
            }
        }
    }
    public function validateIsd()
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $this->isd);
        if (!$dateTime) {
            if ($this->socket == 1) {
                return JsonValidate("Isd is not DateTime format", 0);
            } else {
                PrintJSON("", "Isd is not DateTime format", 0);
                die();
            }
        }
    }
    public function validateExd()
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $this->exd);
        if (!$dateTime) {
            if ($this->socket == 1) {
                return JsonValidate("Exd is not DateTime format", 0);
            } else {
                PrintJSON("", "Exd is not DateTime format", 0);
                die();
            }
        }
    }
    public function validateTel()
    {
        $number = preg_match('@[0-9]@', $this->tel);
        if (!$number || strlen($this->tel) < 10) {
            if ($this->socket == 1) {
                return JsonValidate("Phonenumber must be 10 deigists and number only", 0);
            } else {
                PrintJSON("", "Phonenumber must be 10 deigists and number only", 0);
                die();
            }
        }
    }
    public function validateRfid()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_driver where rfid='$this->rfid' and  job_number != '$this->jno'";
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" RFID NO: " . $this->rfid . " already exist", 0);
            } else {
                PrintJSON("", " RFID NO: " . $this->rfid . " already exist", 0);
                die();
            }
        }
        if ($this->rfid == "") {
            if ($this->socket == 1) {
                return JsonValidate("RFID is empty", 0);
            } else {
                PrintJSON("", "RFID is empty", 0);
                die();
            }
        }
    }
    public function validateAddr()
    {
        if (strlen($this->addr) < 3) {
            if ($this->socket == 1) {
                return JsonValidate("address is short ", 0);
            } else {
                PrintJSON("", "address is short ", 0);
                die();
            }
        }
    }
}
