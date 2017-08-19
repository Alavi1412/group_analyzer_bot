<?php

/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/19/17
 * Time: 12:37 PM
 */
class Group
{
    private $db;

    public function __construct()
    {
        $this->db = mysqli_connect("localhost", "root", "root", "group_analyzer");          //TODO change to real database
        mysqli_set_charset($this->db, "utf8mb4");
    }

    public function getGroupName($id)
    {
        $result = mysqli_query($this->db, "SELECT * FROM `group` WHERE id = '{$id}'");
        $row = mysqli_fetch_array($result);
        return $row['name'];
    }

    public function create($id, $owner_id, $name)
    {
        mysqli_query($this->db, "INSERT INTO `group` (id, owner_id, name) VALUES ({$id}, {$owner_id}, '{$name}')");
    }

    public function checkName($name)
    {
        $result = mysqli_query($this->db, "SELECT * FROM `group` WHERE name = '{$name}'");
        if ($row = mysqli_fetch_array($result))
            return false;
        else
            return true;
    }

    public function getGroupByOwner($owner)
    {
        return mysqli_query($this->db, "SELECT * FROM `group` WHERE owner_id = {$owner} ");
    }

    public function delete($owner, $name)
    {
        mysqli_query($this->db, "DELETE FROM `group` WHERE owner_id = {$owner} AND name = '{$name}'");
    }
}