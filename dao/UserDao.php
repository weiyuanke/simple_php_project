<?php

require_once '../util/TestMysqlUtil.php';

/**
 * UserDao的说明描述
 * 
 * @author weiyuanke
 *
 */
class UserDao
{
    private $mysql = false;
    
    function __construct()
    {
        $this->mysql = new TestMysqlUtl();
    }
    
    /**
     * 获取所有的用户数据
     * 
     * @return number 用户数护具
     */
    function get_all_users()
    {
        $result = false;
        $result = $this->mysql->get_datatable_names();
        $result = $this->mysql->select('wp_users', array('ID' => 1), array('*'));
        //$result = $this->mysql->update('wp_users', array('ID' => 1), array('user_nicename' => 'updated'));
        //$result = $this->mysql->insert('wp_users', array('ID' => 2, 'user_nicename' => 'inserted'));
        //$result = $this->mysql->delete('wp_users', array('ID' => 2));
        //$result = $this->mysql->select('wp_users', array('ID' => 2), array('*'));
        return $result;
    }
    
    /**
     * 根据id获取具体的用户信息
     * 
     * this is a long disce
     * 
     * @param int $id 用户ID
     * @param bool $default 默认返回
     * @return array 用户列表
     */
    function get_user_by_id($id, $default = 12)
    {
        return array();
    }
}