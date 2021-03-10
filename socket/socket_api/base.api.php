<?php

require_once "login.api.php";
require_once "user.api.php";
require_once "group.api.php";
require_once "customer.api.php";
require_once "place.api.php";
require_once "driver.api.php";
require_once "device_object.api.php";
require_once "../../services/services.php";
class BaseAPI
{
    public $command;

    public function __construct()
    {

    }
    public function checkCommand($msg)
    {
        $data = json_decode($msg, true);

        if ($data['command'] == "login") {
            
            $_SESSION["socket"] = 1;

            $api = new LoginAPI();
            return $api->loginMethod($data);
        } else {
            $token = InitializeSocket($data);
            
      
            $this->command = $_SESSION['command'];

            if ($token != 1) {
                return $token;
            }

            $command = getCommandSocket($data);

            switch ($command) {
            case 'user':
                $api = new UserAPI();
                return $api->userMethod($data);
                break;
            case 'group':
                $api = new GroupAPI();
                return $api->groupMethod($data);
                break;
            case 'customer':
                $api = new CustomerAPI();
                return $api->customerMethod($data);
                break;
            case 'place':
                $api = new PlaceAPI();
                return $api->placeMethod($data);
                break;
            case 'driver':
                $api = new DriverAPI();
                return $api->driverMethod($data);
                break;
            case 'device_object':
                $api = new DeviceObjectAPI();
                return $api->deviceObjectMethod($data);
                break;
            default:
                return Responese($this->command, "", "command is not provided!", 0, "", "");
        }
        }
    }
}
