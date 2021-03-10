<?php

require_once "../../controllers/group.controller.php";
require_once "../../models/group.model.php";

class GroupAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function groupMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new GroupServiceController();

        switch ($m) {
            case 'addgroup':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new GroupModel($json_array);

                $group_name = $model->validateName();
                if (!empty($group_name)) {
                    return Responese($this->command, json_encode($group_name), "add group validation error", 0, "", "");
                }
                $group_parent = $model->validateGroup_parent();
                if (!empty($group_parent)) {
                    return Responese($this->command, json_encode($group_parent), "add group validation error", 0, "", "");
                }

                return $control->addGroup($model);

                break;
            case 'deletegroup':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new GroupModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "delete group validation error", 0, "", "");
                }

                return $control->deleteGroup($model->group_id);
                break;
            case 'grouplist':
                $model = (object) $json_array;
                return $control->Grouplist($model);
                break;
            case 'getgroup':
                return $control->getGroup();
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
