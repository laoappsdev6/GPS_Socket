<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include("../controllers/place.controller.php");
include_once("../models/place.model.php");

try {

    Initialization();

    $json = json_decode(file_get_contents('php://input'), true);

    $m = GetMethod();

    $control = new PlaceController();

    if ($m == "addplace") {
        $place = new PlaceModel($json,true);
        $place->checkAllProperties();
        $control->addPlace($place);
    } else if ($m == "updateplace") {
        $place = new PlaceModel($json);
        $place->checkAllProperties();
        $place->checkId();
        $control->updatePlace($place);
    } else if ($m == "deleteplace") {
        $place = new PlaceModel($json,true);
        $place->checkId();
        $control->deletePlace($place);
    } else if ($m == "placelist") {
        $place = (object) $json;
        $control->placeList($place);
    } else {
        PrintJSON("", "Method is not valid", 0);
    }

} catch (Exception $e) {
    print_r($e);
}

?>