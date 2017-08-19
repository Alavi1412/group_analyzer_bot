<?php
/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/19/17
 * Time: 10:46 AM
 */

require "User.php";
require "caption.php";

function makeCurl($method,$datas=[])    //make and receive requests to bot
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot409337188:AAEyYrupyf77cYd88RTgpGiCDsrRzFsWWL8/{$method}");            //TODO change token
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($datas));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
    return $server_output;
}

function getMe()
{
    $result = json_decode(makeCurl("getMe"));
    return $result->result->id;
}
$last_updated_id = 0;           //should be removed
function main()
{
    global $last_updated_id;
//    $update = json_decode(file_get_contents("php://input"));          //should not be comment
    $updates = json_decode(makeCurl("getUpdates",["offset"=>($last_updated_id+1)]));        //should be removed
    if($updates->ok == true && count($updates->result) > 0) {               //should be removed
        foreach ($updates->result as $update) {         //should be removed
//            print_r($update);
            if ($update->message->chat->type == "private" || $update->callback_query->message->chat->type == "private")
            {
                if ($update->callback_query) {
                    makeCurl("answerCallbackQuery", ["callback_query_id" => $update->callback_query->id]);
                    $text = $update->callback_query->data;
                    $user_id = $update->callback_query->from->id;
                    $message_id = $update->callback_query->message->message_id;
                    $user_first_name = $update->callback_query->from->first_name;
                }
                else
                {
                    $text = $update->message->text;
                    $user_id = $update->message->from->id;
                    $message_id = $update->message->message_id;
                    $user_first_name = $update->message->from->first_name;
                }
                $User = new User($user_id, $text, $message_id, $user_first_name);
                $User->process();

            }
            elseif ($update->message->chat->type == "group" || $update->message->chat->type == "supergroup")
            {
                if ($update->message->photo)
                {
                    caption($update->message->caption, $update->message->chat->id);
                }
                elseif($update->message->new_chat_participant)
                {
                    if ($update->message->new_chat_participant->id == getMe())
                    {
                        $user_id = $update->message->from->id;
                        $text = $update->message->chat->id;
                        $message_id = $update->message->message_id;
                        $user_first_name = $update->message->from->first_name;
                        $User = new User($user_id, $text, $message_id, $user_first_name);
                        if ($User->getTemp() == "waiting")
                            $User->process();
                    }
                }
                elseif ($update->message->left_chat_participant)
                {
                    $Group = new Group();
                    if ($update->message->left_chat_participant->id == getMe())
                    {
                        $user_id = $update->message->from->id;
                        $text = $update->message->chat->id;
                        $message_id = $update->message->message_id;
                        $user_first_name = $update->message->from->first_name;
                        $User = new User($user_id, $text, $message_id, $user_first_name);
                        if ($User->getTemp() == $Group->getGroupName($text))
                            $User->process();
                    }
                }
            }
            $last_updated_id = $update->update_id;              //should be removed
        }           //should be removed
    }               //should be removed
}
while(1) {                          //should be removed
    main();
}                               //should be removed