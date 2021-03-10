<?php

require_once "../../controllers/place.controller.php";
require_once "../../models/place.model.php";

class PlaceAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function placeMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new PlaceController();

        switch ($m) {
            case 'addplace':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new PlaceModel($json_array);

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "add place validation error", 0, "", "");
                }

                return $control->addPlace($model);

                break;
            case 'updateplace':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new PlaceModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "update place validation error", 0, "", "");
                }

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "update place validation error", 0, "", "");
                }

                return $control->updatePlace($model);

                break;
            case 'deleteplace':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new PlaceModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "delete customer validation error", 0, "", "");
                }

                return $control->deletePlace($model);
                break;
            case 'placelist':
                $model = (object) $json_array;
                return $control->placeList($model);
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
