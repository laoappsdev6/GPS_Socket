<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "../controllers/customer.controller.php";
include_once "../models/customer.model.php";

try {

    Initialization();
    $m = GetMethod();

    $json = json_decode(file_get_contents('php://input'), true);
    
    $control = new CustomerController();
    if ($m == "addcustomer") {
        $cust = new CustomerModel($json);
        $cust->checkAllProperties();
        $control->addCustomer($cust);

    } else if ($m == "updatecustomer") {
        $cust = new CustomerModel($json);
        $cust->checkId();
        $cust->checkAllProperties();
        $control->updateCustomer($cust);

    } else if ($m == "deletecustomer") {
        $cust = new CustomerModel($json);
        $cust->checkId();
        $control->deleteCustomer($cust);

    } else if ($m == "customerlist") {
        $list = (object) $json;
        $control->CustomerList($list);
    } else {
        PrintJSON("", "method not provided", 0);
    }

} catch (Exception $e) {
    print_r($e);
}
