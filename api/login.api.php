<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
//

include("../controllers/authorize.controller.php");
include_once("../models/login.model.php");
try {

    $json = json_decode(file_get_contents('php://input'), true);


    $log = new LoginModel($json);
    $log->validatelogin();
    $controll = new LoginController();
    $controll->checkLogin($log);
  } catch (Exception $e) {
    print_r($e);
}
