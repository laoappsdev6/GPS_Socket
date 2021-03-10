<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "../controllers/group.controller.php";
include "../models/group.model.php";

try {

    Initialization();

    $json = json_decode(file_get_contents('php://input'), true);
    $m = GetMethod();

    $control = new GroupServiceController();

    if ($m == "addgroup") {
        $model = new GroupModel($json);
        $model->validateName();
        $control->addGroup($model);
    } else if ($m == "deletegroup") {
        $model = new GroupModel($json);
        $control->deleteGroup($model->group_id);
    } else if ($m == "grouplist") {
        $gg = (object) $json;
        $control->Grouplist($gg);
    } else if ($m == "getgroup") {
        $control->getGroup();
    } else if ($m == "groupcustomer") {
        $control->groupCustomer($json);
    } else if ($m == "grouptree") {
        $control->GroupTree();
    } else {
        PrintJSON("", "Method is not valid", 0);
    }
} catch (Exception $e) {
    print_r($e);
}
