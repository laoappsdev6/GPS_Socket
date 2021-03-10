<?php

class CustomerModel
{
    public $custid;
    public $name;
    public $fname;
    public $phone;
    public $remark;

    public $page;
    public $limit;
    public $keyword;

    public $socket;
    public $token;
    public $command;
    public function __construct($object=null, $needEmpty = false)
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
        }
        foreach ($object as $property => $value) {
            if (property_exists('CustomerModel', $property)) {
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
        $sql = "select * from cfg_customer where customer_id='$this->custid' ";
        $name = $db->query($sql);
        
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" customer ID: ".$this->custid. " is not available!", 0);
            } else {
                PrintJSON("", " customer ID: ".$this->custid. " is not available!", 0);
                die();
            }
        } 
    }
    function validate($p)
    {
        switch ($p) {
            case 'name':
                return $this->validateName();
                break;
            case 'fname':
                return $this->validateFname();
                break;
            case 'phone':
                return $this->validatePhone();
                break;
        }
    }
    function validateName()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_customer where short_name='$this->name' and customer_id!='$this->custid' ";
        $name = $db->query($sql);
        
        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" short name: ".$this->name. " already exit!", 0);
            } else {
                PrintJSON("", " short name: ".$this->name. " already exit!", 0);
                die();
            }
        } 
        else if (strlen($this->name) < 2) {
            if ($this->socket == 1) {
                return JsonValidate("Name is short ", 0);
            } else {
                PrintJSON("", "Name is short ", 0);
                die();
            }
        }
    }
    function validateFname()
    { 
        $db = new db_mssql();
        $sql = "select * from cfg_customer where full_name='$this->fname' and customer_id!='$this->custid' ";
        $name = $db->query($sql);
        
        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate(" full name: ".$this->fname. " already exit!", 0);
            } else {
                PrintJSON("", " full name: ".$this->fname. " already exit!", 0);
                die();
            }
        } 
        if (strlen($this->fname) < 3) {
            if ($this->socket == 1) {
                return JsonValidate("Full name is short ", 0);
            } else {
                PrintJSON("", "Full name is short ", 0);
                die();
            }
        }
    }
    function validatePhone()
    {
        $number = preg_match('@[0-9]@', $this->phone);
        if (!$number || strlen($this->phone) < 10) {
            if ($this->socket == 1) {
                return JsonValidate("Phonenumber must be 10 deigists and number only", 0);
            } else {
                PrintJSON("", "Phonenumber must be 10 deigists and number only", 0);
                die();
            }
        }
    }
}
?>