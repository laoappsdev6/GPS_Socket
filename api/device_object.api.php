<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "../controllers/device_object.controller.php";
include_once "../models/device_object.model.php";

try {

    Initialization();
    $m = GetMethod();

    $json = json_decode(file_get_contents('php://input'), true);

    $control = new DeviceObjectController();

    if ($m == "addobject") {
        $dobj = new DeviceObjectModel($json);
        $dobj->checkAllProperties();
        $control->addDeviceObject($dobj);
    } else if ($m == "updateobject") {
        $dobj = new DeviceObjectModel($json);
        $dobj->checkId();
        $dobj->checkAllProperties();
        $control->udpateDeviceObject($dobj);
    } else if ($m == "deletedevice") {
        $dobj = new DeviceObjectModel($json);
        $dobj->checkId();
        $control->deleteObject($dobj->objid);
    } else if ($m == "objectlist") {
        $dobj = (object) $json;
        $control->Objectlist($dobj);
    } else if ($m == "objectlistpage") {
        $page = (object) $json;
        $control->ObjectListPage($page);
    } else if ($m == "objectlist_one") {
        $page = (object) $json;
        $control->ObjectListby_imei($page);
    } else if ($m == "change_gps") {
        $gps = (object) $json;
        $control->changeGPS($gps);
    } else if ($m == "getinstall_date") {
        $dobj = new DeviceObjectModel($json);
        $dobj->checkId();
        $control->getInstall_date($dobj->objid);
    } else if ($m == "get_details_category") {
        $control->get_details_categroy($json);
    } else if ($m == "server_expired") {
        $gps = (object) $json;
        $control->serverExpired($gps);
    }else if ($m == "device_online") {
        $gps = (object) $json;
        $control->deviceOnline($gps);
    } else if ($m == "getdtype") {
        $control->getDtype();
    } else if ($m == "search_gps") {
        $page = (object) $json;
        $control->searchGPS($page);
    }  else {
        PrintJSON("", "Method is not valid", 0);
    }

} catch (Exception $e) {
    print_r($e);
}
