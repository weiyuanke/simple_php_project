<?php

require_once '../base/MysqlDaoBase.php';

class TestMysqlUtl extends MysqlDaoBase
{
    protected function host()
    {
        return MYSQL_HOST;
    }
    
    protected function user()
    {
        return MYSQL_USER;
    }
    
    protected function passwd()
    {
        return MYSQL_PWD;
    }
    
    protected function database()
    {
        return MYSQL_TEST_DB;
    }
}