<?php

class DeviceObjectModel
{
    public $objid;
    public $dtype;
    public $dstate;
    public $devno;
    public $simno;
    public $dpass;
    public $stamp;
    public $iaddr;
    public $estamp;
    public $cinfo;
    public $ginfo;
    public $okind;
    public $oflag;
    public $uflag;
    public $ztime;
    public $driver;
    public $remark;

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
            if (property_exists('DeviceObjectModel', $property)) {
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
        $sql = "select * from cfg_object where object_id='$this->objid' ";
        $name = $db->query($sql);

        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" object ID: " . $this->objid . " is not available!", 0);
            } else {
                PrintJSON("", " object ID: " . $this->objid . " is not available!", 0);
                die();
            }
        }
    }
    public function validate($p)
    {
        switch ($p) {
            case 'dtype':
                return $this->validateDtype();
                break;
            case 'dstate':
                return $this->validateDstate();
                break;
            case 'devno':
                return $this->validateDevno();
                break;
            case 'simno':
                return $this->validateSimno();
                break;
            case 'stamp':
                return $this->validateStamp();
                break;
            case 'estamp':
                return $this->validateEstamp();
                break;
            case 'cinfo':
                return $this->validateCinfo();
                break;
            case 'ginfo':
                return $this->validateGinfo();
                break;
            case 'oflag':
                return $this->validateOflag();
                break;
            case 'ztime':
                return $this->validateZtime();
                break;
            case 'driver':
                return $this->validateDriver();
                break;
        }
    }

    public function validateDtype()
    {
        if (!is_numeric($this->dtype)) {
            if ($this->socket == 1) {
                return JsonValidate("Dtype is number only", 0);
            } else {
                PrintJSON("", "Dtype is number only", 0);
                die();
            }
        }
    }
    public function validateDstate()
    {
        if (!is_numeric($this->dstate)) {
            if ($this->socket == 1) {
                return JsonValidate("Dstate is number only", 0);
            } else {
                PrintJSON("", "Dstate is number only", 0);
                die();
            }
        }
    }
    public function validateDevno()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_device where device_no='$this->devno' and object_id !='$this->objid' ";
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" Device NO: " . $this->devno . " already exist", 0);
            } else {
                PrintJSON("", " Device NO: " . $this->devno . " already exist", 0);
                die();
            }
        }
        if (!is_numeric($this->devno)) {
            if ($this->socket == 1) {
                return JsonValidate("Device NO is number only", 0);
            } else {
                PrintJSON("", "Device NO is number only", 0);
                die();
            }
        }
    }
    public function Devno_changeGPS()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_device where device_no='$this->devno'";
        $name = $db->query($sql);

        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" Device NO: " . $this->devno . " is not available", 0);
            } else {
                PrintJSON("", " Device NO: " . $this->devno . " is not available", 0);
                die();
            }
        }
        if (!is_numeric($this->devno)) {
            if ($this->socket == 1) {
                return JsonValidate("Device NO is number only", 0);
            } else {
                PrintJSON("", "Device NO is number only", 0);
                die();
            }
        }
    }
    public function validateSimno()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_device where device_sim='$this->simno' and object_id !='$this->objid'";
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" Sim NO: " . $this->simno . " already exist", 0);
            } else {
                PrintJSON("", " Sim NO: " . $this->simno . " already exist", 0);
                die();
            }
        } else if (!is_numeric($this->simno)) {
            if ($this->socket == 1) {
                return JsonValidate("Device sim is number only", 0);
            } else {
                PrintJSON("", "Device sim is number only", 0);
                die();
            }
        }
    }
    public function validateStamp()
    {
        $dateTime = DateTime::createFromFormat('Y-m-d h:i:s', $this->stamp);
        if (!$dateTime) {
            if ($this->socket == 1) {
                return JsonValidate("stamp is not DateTime format", 0);
            } else {
                PrintJSON("", "stamp is not DateTime format", 0);
                die();
            }
        }
    }
    public function validateEstamp()
    {
        $dateTime = DateTime::createFromFormat('Y-m-d h:i:s', $this->estamp);
        if (!$dateTime) {
            if ($this->socket == 1) {
                return JsonValidate("expired is not DateTime format", 0);
            } else {
                PrintJSON("", "expired is not DateTime format", 0);
                die();
            }
        }
    }
    public function validateCinfo()
    {
        if (!is_numeric($this->cinfo)) {
            if ($this->socket == 1) {
                return JsonValidate("Customer id is number only", 0);
            } else {
                PrintJSON("", "Customer id is number only", 0);
                die();
            }
        }
    }
    public function validateGinfo()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_group where group_id='$this->ginfo' ";
        $name = $db->query($sql);

        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" group ID: " . $this->ginfo . " is not available!", 0);
            } else {
                PrintJSON("", " group ID: " . $this->ginfo . " is not available!", 0);
                die();
            }
        }
    }
    public function validateOflag()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_object where object_flag=N'$this->oflag' and object_id !='$this->objid'";
        // echo $sql;die();
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" Object flag: " . $this->oflag . " already exist", 0);
            } else {
                PrintJSON("", " Object flag: " . $this->oflag . " already exist", 0);
                die();
            }
        }
        if ($this->oflag == "") {
            if ($this->socket == 1) {
                return JsonValidate("Object Flag is empty", 0);
            } else {
                PrintJSON("", "Object Flag is empty", 0);
                die();
            }
        }
    }

    public function validateZtime()
    {
        if ($this->ztime == "") {
            if ($this->socket == 1) {
                return JsonValidate("Time Zone is not valid", 0);
            } else {
                PrintJSON("", "Time Zone is not valid", 0);
                die();
            }
        }
    }
    public function validateDriver()
    {
        if (empty($this->driver) || $this->driver ==" ") {
            if ($this->socket == 1) {
                return JsonValidate(" driver is empty!", 0);
            } else {
                PrintJSON("", " driver is empty!", 0);
                die();
            }
        }
    }
}
