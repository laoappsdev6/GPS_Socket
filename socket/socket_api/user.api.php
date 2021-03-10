<?php

require_once "../../controllers/user.controller.php";
require_once "../../models/user.model.php";

class UserAPI
{
    public $command;

    public function __construct()
    {
        $this->command = $_SESSION['command'];
    }
    public function userMethod($data)
    {

        $m = getMethodSocket($data);
        $json_array = getData($data);

        $control = new UserController();

        switch ($m) {
            case 'adduser':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new UserModel($json_array);

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "add new user validation error", 0, "", "");
                }

                return $control->addUser($model);

                break;
            case 'updateuser':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new UserModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "update user validation error", 0, "", "");
                }

                $validate = $model->checkAllProperties();
                if (count($validate) > 0) {
                    return Responese($this->command, $validate, "update user validation error", 0, "", "");
                }

                return $control->updateUser($model);

                break;
            case 'deleteuser':
                if (empty($json_array)) {
                    return Responese($this->command, "", "data is empty!", 0, "", "");
                }

                $model = new UserModel($json_array);

                $check_id = $model->checkId();
                if (!empty($check_id)) {
                    return Responese($this->command, json_encode($check_id), "delete user validation error", 0, "", "");
                }

                return $control->deleteUser($model);
                break;
            case 'userlist':
                $user = new UserModel($json_array);
                $usrid = $user->usrid_;
                if (IsMyselfSocket($data)) {
                    $usrid = $_SESSION["uid"];
                }
                return $control->userlist($usrid);
                break;
            case 'userlistpage':
                $model = (object) $json_array;
                return $control->UserListPage($model);
                break;
            case 'userlist_one':
                $page = (object) $json_array;
                return $control->userList_one($page);
                break;
            case 'userlistgroup':
                $page = (object) $json_array;
                return $control->UserListGroup($page);
                break;
            case 'changepassword':
                $page = (object) $json_array;
                return $control->changePassword($page);
                break;
            default:
                return Responese($this->command, "", "method is not provided!", 0, "200", "123456");
        }
    }
}
