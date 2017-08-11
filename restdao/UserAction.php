<?php

require_once '../base/RestifyBase.php';
require_once '../dao/UserDao.php';

class UserAction extends RestifyBase
{
    function __construct()
    {
        if (isset(DelegatorUtil::$dao_acion_dict[get_class($this)]))
        {
            $daoclassname = DelegatorUtil::$dao_acion_dict[get_class($this)];
        }
        else
        {
            die_add_header(
                    array(
                            'status' => ERROR_STATUS, 'code' => ERROR_CODE,
                            'reason' => '初始化失败')
                    );
        }
        parent::__construct($daoclassname);
    }
}

$action = new UserAction();
$action->safe_execute();