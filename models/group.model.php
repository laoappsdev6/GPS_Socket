<?php

class GroupModel
{
    public $group_id;
    public $group_name;
    public $group_parent;

    public $page;
    public $limit;
    public $keyword;

    public $socket;
    public $token;
    public $command;

    public function __construct($object)
    {
        $this->socket = $_SESSION["socket"];
        $this->token = $_SESSION["token"];
        $this->command = $_SESSION["command"];
            if (!$object) {
                if ($this->socket <> 1) {
                    PrintJSON("", "data is empty", 0);
                    die();
                }
        }
        foreach ($object as $property => $value) {
            if (property_exists('GroupModel', $property)) {
                $this->$property = $value;
            }
        }
    }
    function checkId()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_group where group_id='$this->group_id' ";
        $name = $db->query($sql);
        
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" group ID: " . $this->group_id . " is not available!", 0);
            } else {
                PrintJSON("", " group ID: " . $this->group_id . " is not available!", 0);
                die();
            }
        } 
    }
    function validateName()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_group where group_name='$this->group_name' ";
        $name = $db->query($sql);

        if ($name > 0) {
            if ($this->socket == 1) {
                return JsonValidate("group name: ".$this->group_name. " already exist", 0);
            } else {
                PrintJSON("", " group name: ".$this->group_name. " already exist", 0);
                die();
            }
        }
        if (empty($this->group_name)) {
            if ($this->socket == 1) {
                return JsonValidate(" group name is empty ", 0);
            } else {
                PrintJSON("", " group name is empty ", 0);
                die();
            }
        }
    }
    function validateGroup_parent()
    {
        $db = new db_mssql();
        $sql = "select * from cfg_group where group_id='$this->group_parent' ";
        $name = $db->query($sql);
        
        if ($name == 0) {
            if ($this->socket == 1) {
                return JsonValidate(" group parent ID: ".$this->group_parent. " is not available!", 0);
            } else {
                PrintJSON("", " group parent ID: ".$this->group_parent. " is not available!", 0);
                die();
            }
        } 
    }
}
