<?php

require_once "../../controllers/driver.controller.php";
require_once "../../models/driver.model.php";

class DriverAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function driverMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new DriverController();

        switch ($m) {
            case 'adddriver':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DriverModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "update dirver validation error", 0, "", "");
                }

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "add driver validation error", 0, "", "");
                }

                return $control->addDriver($model);

                break;
            case 'updatedriver':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DriverModel($json_array);

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "update driver validation error", 0, "", "");
                }

                return $control->updateDriver($model);

                break;
            case 'deletedriver':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DriverModel($json_array);

                return $control->deleteDriver($model);
                break;
            case 'driverlist':
                $model = (object) $json_array;
                return $control->DriverList($model);
                break;
            case 'driverlistpage':
                $model = (object) $json_array;
                return $control->driverListPage($model);
                break;
            case 'getdriver':
                $model = (object) $json_array;
                return $control->getDriver($model);
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
