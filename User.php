<?php

/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/19/17
 * Time: 11:05 AM
 */

require "Group.php";

class User
{
    private $text;
    private $user_id;
    private $password;
    private $level;
    private $message_id;
    private $db;
    private $user_first_name;

    public function __construct($user_id, $text, $message_id, $user_first_name)
    {
        $this->user_id = $user_id;
        $this->text = $text;
        $this->message_id = $message_id;
        $this->user_first_name = $user_first_name;
        $this->db = mysqli_connect("localhost", "root", "root", "group_analyzer");      //TODO change database info
        mysqli_set_charset($this->db, "utf8mb4");
        $this->getLevelAndPassword();
    }

    private function getLevelAndPassword()
    {
        $result = mysqli_query($this->db, "SELECT * FROM user WHERE user_id = {$this->user_id}");
        if ($row = mysqli_fetch_array($result))
        {
            $this->level =  $row['level'];
            $this->password = $row['password'];
        }
        else
            $this->level = "notFound";

    }

    public function getTemp()
    {
        $result = mysqli_query($this->db, "SELECT * FROM user WHERE user_id = {$this->user_id}");
        $row = mysqli_fetch_array($result);
        return $row['temp'];
    }

    private function setTemp($temp)
    {
        mysqli_query($this->db, "UPDATE user SET temp = '{$temp}' WHERE user_id = {$this->user_id}");
    }

    private function getRow()
    {
        $result = mysqli_query($this->db, "SELECT * FROM user WHERE user_id = {$this->user_id}");
        $row = mysqli_fetch_array($result);
        return $row;
    }

    private function setTempNull()
    {
        mysqli_query($this->db, "UPDATE user SET temp = NULL WHERE user_id = {$this->user_id}");
    }

    private function setLevel($level)
    {
        mysqli_query($this->db, "UPDATE user SET level = '{$level}' WHERE user_id = {$this->user_id}");
    }

    private function sendMessage($text, $inline)
    {
        $result = json_decode($this->makeCurl("sendMessage", ["chat_id" => $this->user_id, "text" => $text, "reply_markup" => json_encode([
            "inline_keyboard" =>
                $inline
        ])]));
        $message_id = $result->result->message_id;
        mysqli_query($this->db, "UPDATE user SET last_message_id = {$message_id} WHERE user_id = {$this->user_id}");
    }

    private function editMessageText($text, $inline)
    {
        $result = json_decode($this->makeCurl("editMessageText", ["message_id" => $this->message_id ,"chat_id" => $this->user_id, "text" => $text, "reply_markup" => json_encode([
            "inline_keyboard" =>
                $inline
        ])]));
        $message_id = $result->result->message_id;
        mysqli_query($this->db, "UPDATE user SET last_message_id = {$message_id} WHERE user_id = {$this->user_id}");
    }

    public function process()
    {
        if ($this->level == "notFound")
            $this->showNotFound();
        elseif ($this->level == "ask_password")
            $this->passwordManager();
        elseif ($this->level == "main_menu_showed")
            $this->mainMenuManager();
        elseif ($this->level == "adding_group")
            $this->addGroupManager();
        elseif ($this->level == "deleting_group" || $this->level == "waiting_to_delete")
            $this->deleteGroupManager();
    }

    private function mainMenuManager()
    {
        if ($this->text == "Add_Group")
            $this->addGroupManager();
        elseif ($this->text == "Delete_Group")
            $this->deleteGroupManager();
        else
        {
            $this->sendMessage("پیام ارسال شده معتبر نمی باشد.", []);
            $this->showMainMenu(false);
        }
    }

    private function addGroupManager()
    {
        $this->setLevel("adding_group");
        if ($this->getTemp() == NULL)
        {
            $this->editMessageText("بات را در گروه اد کنید.", []);
            $this->setTemp("waiting");
        }
        elseif ($this->getTemp() == "waiting")
        {
            if (is_numeric($this->text))
            {
                $data = $this->getRow();
                $this->setTemp($this->text);
                $this->makeCurl("editMessageText", ["chat_id" => $this->user_id, "message_id" => $data['last_message_id'], "text" => "اسم گروه رو به دلخواه وارد کنید:"]);
            }
            else
            {
                $this->sendMessage("گرو اضافه نشد.", []);
                $this->showMainMenu(false);
            }
        }
        elseif ( ($this->getTemp() != "waiting") && ($this->getTemp() != NULL) )
        {
            $GrouP = new Group();
            if ($GrouP->checkName($this->text))
            {
                $result = json_decode($this->makeCurl("sendMessage", ["chat_id" => $this->getTemp(), "text" => "گروه اضافه شد."]));
                if ($result->ok == true) {
                    $this->makeCurl("deleteMessage", ["chat_id" => $this->getTemp(), "message_id" => $result->result->message_id]);
                    $Group = new Group();
                    $Group->create($this->getTemp(), $this->user_id, $this->text);
                    $this->createTable($this->text);
                    $this->sendMessage("گروه اضافه شد.", []);
                    $this->showMainMenu(false);
                } else {
                    $this->sendMessage("گرو اضافه نشد.", []);
                    $this->showMainMenu(false);
                }
            }else
            {
                $this->sendMessage("نام وارد شده تکراری می باشد. مجددا سعی کنید.", []);
            }

        }
    }

    private function deleteGroupManager()
    {
        $this->setLevel("deleting_group");
        if ($this->getTemp() == NULL)
        {
            $Group = new Group();
            $this->setTemp("group_asked");
            $result = $Group->getGroupByOwner($this->user_id);
            if ($row = mysqli_fetch_array($result))
            {
                $arr = [ [ ["text" => $row['name'], "callback_data"=> $row['name'] ] ] ];
                while ($row = mysqli_fetch_array($result))
                    array_push($arr, [ ["text" => $row['name'], "callback_data"=> $row['name'] ] ]);
                array_push($arr, [ ["text" => "بازگشت", "callback_data" => "Return"] ]);
                $this->editMessageText("گروه مورد نظر را انتخاب کنید.", $arr);
            }
            else
            {
                $this->editMessageText("گروهی یافت نشد.", []);
                $this->showMainMenu(false);
            }
        }
        elseif ($this->getTemp() == "group_asked")
        {
            if ($this->text == "Return")
                $this->showMainMenu(true);
            else
            {
                $this->setTemp($this->text);
                $this->setLevel("waiting_to_delete");
                $this->editMessageText("بات را از گروه پاک کنید.", []);
            }
        }
        elseif ($this->level == "waiting_to_delete")
        {
            if (is_numeric($this->text))
            {
                $data = $this->getRow();
                $this->makeCurl("editMessageText", ["text" => "گروه حذف شد.", "chat_id" => $this->user_id, "message_id" => $data['last_message_id']]);
                $Group = new Group();
                $Group->delete($this->user_id, $this->getTemp());
                $this->deleteTable($this->getTemp());
                $this->showMainMenu(false);
            }
            else
            {
                $this->sendMessage("گروه حذف نشد.", []);
                $this->showMainMenu(false);
            }
        }

    }

    private function showMainMenu($editStatus)
    {
        $this->setLevel("main_menu_showed");
        $this->setTempNull();
        $title = "انتخاب کنید";
        $button = [
            [
                ["text" => "اضافه کردن گروه", "callback_data" => "Add_Group"]
            ],
            [
                ["text" => "حذف گروه", "callback_data" => "Delete_Group"]
            ]
        ];
        if ($editStatus)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function passwordManager()
    {
        if ($this->text == $this->password)
            $this->showMainMenu(false);
        else
            $this->sendMessage("رمز وارد شده اشتباه است.
مجددا سعی کنید.", []);
    }

    private function showNotFound()
    {
        $this->sendMessage("آیدی شما:", []);
        $this->sendMessage($this->user_id, []);
        $this->sendMessage("شما مجاز به استفاده از بات نیستید.", []);
        mysqli_query($this->db, "INSERT INTO unkown_user (user_id, user_first_name) VALUES ({$this->user_id}, '{$this->user_first_name}')");
    }

    private function createTable($name)
    {
        mysqli_query($this->db, "CREATE TABLE `{$name}` (
`channel_name` varchar(256) CHARACTER SET utf8mb4 DEFAULT NULL,
`channel_id` varchar(256) CHARACTER SET utf8mb4 DEFAULT NULL,
`username` varchar(256) CHARACTER SET utf8mb4 DEFAULT NULL,
`name` varchar(256) CHARACTER SET utf8mb4 DEFAULT NULL,
`rate` int(11) DEFAULT NULL,
`view` double DEFAULT NULL,
`card_number` varchar(256) DEFAULT NULL,
`shaba` varchar(256) DEFAULT NULL)");
    }

    private function deleteTable($name)
    {
        mysqli_query($this->db, "DROP TABLE `{$name}`");
    }

    function makeCurl($method,$datas=[])    //make and receive requests to bot TODO change token
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot409337188:AAEyYrupyf77cYd88RTgpGiCDsrRzFsWWL8/{$method}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($datas));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        return $server_output;
    }
}