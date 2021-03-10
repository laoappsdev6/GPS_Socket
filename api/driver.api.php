<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "../controllers/driver.controller.php";
include_once "../models/driver.model.php";

try {

    Initialization();
    $m = GetMethod();

    $json = json_decode(file_get_contents('php://input'), true);

    $control = new DriverController();

    if ($m == "adddriver") {
        $driver = new DriverModel($json);
        $driver->checkId();
        $driver->checkAllProperties();
        $control->addDriver($driver);
    } else if ($m == "updatedriver") {
        $driver = new DriverModel($json);
        $driver->checkAllProperties();
        $control->updateDriver($driver);
    } else if ($m == "deletedriver") {
        $driver = new DriverModel($json);
        $driver->checkId();
        $control->deleteDriver($driver);
    } else if ($m == "driverlist") {
        $driver = (object) $json;
        $control->DriverList($driver);
    } else if ($m == "driverlistpage") {
        $driver = (object) $json;
        $control->driverListPage($driver);
    } else if ($m == "getdriver") {
        $control->getDriver();
    } else {
        PrintJSON("", "Method is not valid", 0);
    }

} catch (Exception $e) {
    print_r($e);
}
