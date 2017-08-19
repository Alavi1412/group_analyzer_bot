<?php
/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/19/17
 * Time: 1:45 PM
 */

function convert($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

    $num = range(0, 9);
    $convertedPersianNums = str_replace($persian, $num, $string);
    $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

    return $englishNumbersOnly;
}

function caption($text, $group_id)
{
    $texts = explode("\n", $text);
    $Group = new Group();
    $table_name = $Group->getGroupName($group_id);
    $db = mysqli_connect("localhost", "root", "root", "group_analyzer");            //TODO change to real database
    mysqli_set_charset($db, "utf8mb4");
    $i = 0;
    while ($texts[$i] == "\n")
        $i++;
    $channel_name = $texts[$i];


    $i++;
    while ($texts[$i] == "\n")
        $i++;
    $channel_id = $texts[$i];


    $i++;
    while ($texts[$i] == "\n")
        $i++;
    $username = $texts[$i];


    $i++;
    while ($texts[$i] == NULL)
        $i++;
    $name = $texts[$i];


    $i++;
    while ($texts[$i] == NULL)
        $i++;
    $rate = convert($texts[$i]);


    $i++;
    while ($texts[$i] == NULL)
        $i++;
    $view = str_replace("/",".", $texts[$i]);
    $view = convert($view);


    $i++;
    while ($texts[$i] == NULL)
        $i++;
    $card_number = $texts[$i];
    $card_number = str_replace(" ", "", $card_number);
    $card_number = str_replace("-", "", $card_number);

    $i++;
    while ($texts[$i] == NULL)
        $i++;
    $shaba_number = $texts[$i];
    $shaba_number = str_replace("IR", "", $shaba_number);
    $shaba_number = str_replace("ir", "", $shaba_number);
    $shaba_number = str_replace("Ir", "", $shaba_number);
    $shaba_number = str_replace("iR", "", $shaba_number);
    $shaba_number = str_replace("-", "", $shaba_number);
    $shaba_number = str_replace(" ", "", $shaba_number);

    if ($shaba_number)
        mysqli_query($db, "INSERT INTO `{$table_name}` (`channel_name`, `channel_id`, `username`, `name`, `rate`, `view`, `card_number`, `shaba`) VALUES ('{$channel_name}', '{$channel_id}', '{$username}', '{$name}', {$rate}, {$view}, '{$card_number}', '{$shaba_number}')");
    else
        mysqli_query($db, "INSERT INTO `{$table_name}` (`channel_name`, `channel_id`, `username`, `name`, `rate`, `view`, `card_number`) VALUES ('{$channel_name}', '{$channel_id}', '{$username}', '{$name}', {$rate}, {$view}, '{$card_number}')");

}