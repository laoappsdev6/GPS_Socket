<?php

class PlaceModel
{

    public $zid;
    public $aname;
    public $acolor;
    public $atype;
    public $apts;
    public $zoom;
    public $es;
    public $ins;

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
            if (property_exists('PlaceModel', $property)) {
                $this->$property = $value;
            }
        }
    }

    function checkAllProperties()
    {
        $result = array();
        foreach ($this as $property => $value) {
            $check = $this->validate($property);
            @sizeof($check)>0?$result[]=$check:'';
        }
        return $result;
    }

    function checkId()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_place where place_id='$this->zid' ";
        $name = $db->query($sql);
        
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" place ID: ".$this->zid. " is not available!", 0);
            } else {
                PrintJSON("", " place ID: ".$this->zid. " is not available!", 0);
                die();
            }
        } 
    }
    function validate($p)
    {
        switch ($p) {
            case 'aname':
                return $this->validateArea_name();
                break;
            case 'acolor':
                return $this->validateArea_color();
                break;
            case 'atype':
                return $this->validateArea_type();
                break;
            case 'apts':
                return $this->validateArea_pts();
                break;
            case 'zoom':
                return $this->validateZoom();
                break;
            case 'es':
                return $this->validateEs();
                break;
            case 'ins':
                return $this->validateIns();
                break;
        }
    }

    function validateArea_name()
    {
        if (strlen($this->aname) < 3) {
            if ($this->socket == 1) {
                return JsonValidate("Area name is short", 0);
            } else {
                PrintJSON("", "Area name is short ", 0);
                die();
            }
        }
    }
    function validateArea_color()
    {
        $color = preg_match_all('/#(?:[0-9a-fA-F]{6})/', $this->acolor);
        if (!$color) {
            if ($this->socket == 1) {
                return JsonValidate("Color is not valid", 0);
            } else {
                PrintJSON("", "Color is not valid ", 0);
                die();
            }
        }
    }
    function validateArea_type()
    {
        if (!is_numeric($this->atype)) {
            if ($this->socket == 1) {
                return JsonValidate("Area type is number only", 0);
            } else {
                PrintJSON("", "Area type is number only", 0);
                die();
            }
        }
    }
    function validateArea_pts()
    {
        if ($this->apts == "") {
            if ($this->socket == 1) {
                return JsonValidate("", 0);
            } else {
                PrintJSON("", "Area pts value invalid", 0);
                die();
            }
        }
    }
    function validateZoom()
    {
        if (!is_numeric($this->zoom)) {
            if ($this->socket == 1) {
                return JsonValidate("Area zoom is number only", 0);
            } else {
                PrintJSON("", "Area zoom is number only", 0);
                die();
            }
        }
    }
    function validateEs()
    {
        if (!is_numeric($this->es)) {
            if ($this->socket == 1) {
                return JsonValidate("Enable speed limit is number only", 0);
            } else {
                PrintJSON("", "Enable speed limit is number only", 0);
                die();
            }
        }
    }
    function validateIns()
    {
        if (!is_numeric($this->ins)) {
            if ($this->socket == 1) {
                return JsonValidate("Inside speed limit is number only", 0);
            } else {
                PrintJSON("", "Inside speed limit is number only", 0);
                die();
            }
        }
    }

}

?>