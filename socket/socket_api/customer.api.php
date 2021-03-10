<?php

require_once "../../controllers/customer.controller.php";
require_once "../../models/customer.model.php";

class CustomerAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function customerMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new CustomerController();

        switch ($m) {
            case 'addcustomer':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new CustomerModel($json_array);

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "add customer validation error", 0, "", "");
                }

                return $control->addCustomer($model);

                break;
            case 'updatecustomer':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new CustomerModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "update customer validation error", 0, "", "");
                }

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "update customer validation error", 0, "", "");
                }

                return $control->updateCustomer($model);

                break;
            case 'deletecustomer':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new CustomerModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "delete customer validation error", 0, "", "");
                }

                return $control->deleteCustomer($model);
                break;
            case 'customerlist':
                $model = (object) $json_array;
                return $control->CustomerList($model);
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
