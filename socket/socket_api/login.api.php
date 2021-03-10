<?php

require_once "../../controllers/authorize.controller.php";
require_once "../../models/login.model.php";

class LoginAPI
{

    public function __construct()
    {
    }
    public function loginMethod($data)
    {

        $json_array = getData($data);

        $control = new LoginController();

        if (empty($json_array)) {
            return Responese("login", "", "data is empty!", 0, "", "");
        }

        $model = new LoginModel($json_array);

        $validate = $model->validatelogin();
        if (!empty($validate)) {
            return Responese("login", json_encode($validate), "login validation error", 0, "", "");
        }

        return $control->checkLogin($model);

    }
}
