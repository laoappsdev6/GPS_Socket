<?php

class LoginModel
{
    public $username;
    public $password;
    public $token;

    public $socket;
    public function __construct($object)
    {
        $this->socket = $_SESSION["socket"];

        foreach ($object as $property => $value) {
            if (property_exists('LoginModel', $property)) {
                $this->$property = $value;
            }
        }
    }

    function validatelogin()
    {
        if ($this->username =="" && $this->password == ""){
            if ($this->socket == 1) {
                return JsonValidate("username and password is empty", 0);
            } else {
                PrintJSON("", "username and password is empty", 0);
                die();
            }
        }else if ($this->username == "") {
            if ($this->socket == 1) {
                return JsonValidate("username is empty ", 0);
            } else {
                PrintJSON("", "username is empty ", 0);
                die();
            }
        }elseif ($this->password == "") {
            if ($this->socket == 1) {
                return JsonValidate("password is empty ", 0);
            } else {
                PrintJSON("", "password is empty ", 0);
                die();
            }
        }
    }  
}
