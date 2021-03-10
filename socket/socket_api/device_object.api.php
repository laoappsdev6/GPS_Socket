<?php

require_once "../../controllers/device_object.controller.php";
require_once "../../models/device_object.model.php";

class DeviceObjectAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function deviceObjectMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new DeviceObjectController();

        switch ($m) {
            case 'addobject':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DeviceObjectModel($json_array);

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "add device and object validation error", 0, "", "");
                }

                return $control->addDeviceObject($model);

                break;
            case 'updateobject':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DeviceObjectModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "update device and object validation error", 0, "", "");
                }

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "update device and object validation error", 0, "", "");
                }

                return $control->udpateDeviceObject($model);

                break;
            case 'deletedevice':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new DeviceObjectModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "delete customer validation error", 0, "", "");
                }

                return $control->deleteObject($model->objid);
                break;
            case 'objectlist':
                $model = (object) $json_array;
                return $control->Objectlist($model);
                break;
            case 'objectlistpage':
                $model = (object) $json_array;
                return $control->ObjectListPage($model);
                break;
            case 'objectlist_one':
                $model = (object) $json_array;
                return $control->ObjectListby_imei($model);
                break;
            case 'change_gps':
                $model = (object) $json_array;
                return $control->changeGPS($model);
                break;
            case 'getinstall_date':
                $model = (object) $json_array;
                return $control->getInstall_date($model->objid);
                break;
            case 'get_details_category':
                return $control->get_details_categroy($json_array);
                break;
            case 'server_expired':
                $model = (object) $json_array;
                return $control->serverExpired($json_array);
                break;
            case 'device_online':
                $model = (object) $json_array;
                return $control->deviceOnline($model);
                break;
            case 'getdtype':
                $model = (object) $json_array;
                return $control->getDtype($model);
                break;
            case 'search_gps':
                $model = (object) $json_array;
                return $control->searchGPS($model);
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
