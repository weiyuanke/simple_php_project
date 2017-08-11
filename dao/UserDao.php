<?php

require_once '../util/TestMysqlUtil.php';

class UserDao
{
    private $mysql = false;
    
    function __construct()
    {
        $this->mysql = new TestMysqlUtl();
    }
    
    function get_all_users()
    {
        $result = false;
        $result = $this->mysql->get_datatable_names();
        $result = $this->mysql->select('wp_users', array('ID' => 2), array('*'));
        //$result = $this->mysql->update('wp_users', array('ID' => 1), array('user_nicename' => 'updated'));
        //$result = $this->mysql->insert('wp_users', array('ID' => 2, 'user_nicename' => 'inserted'));
        $result = $this->mysql->delete('wp_users', array('ID' => 2));
        //$result = $this->mysql->select('wp_users', array('ID' => 2), array('*'));
        return $result;
    }
}