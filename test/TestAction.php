<?php

require_once '../base/ActionBase.php';

class TestAction extends ActionBase
{
    public function __construct($init_degradation = false)
    {
        parent::__construct();
        if (in_array($init_degradation, self::$ALL_SERVICE_LEVELS))
        {
            $this->degradation_level = $init_degradation;
        }
    }

    protected function contact()
    {
        return WEIYUANKE_EMAIL;
    }

    protected function execute()
    {
        if ($this->degradation_level === SERVICE_DEGRADATION_NORMAL)
        {
            return array(
                'status' => SUCCESS_STATUS, 'code' => SUCCESS_CODE, 'result' => array(),
                'msg' => "server is normal"
            );
        }
        
        if ($this->degradation_level === SERVICE_DEGRADATION_BASIC)
        {
            return array(
                'status' => SUCCESS_STATUS, 'code' => SUCCESS_CODE, 'result' => array(),
                'msg' => "basic service"
            );
        }
        
        if ($this->degradation_level === SERVICE_DEGRADATION_DISABLED)
        {
            return array(
                'status' => SUCCESS_STATUS, 'code' => SUCCESS_CODE, 'result' => array(),
                'msg' => "service disabled"
            );
        }
    }
}

/**
 * 手动修改控制服务级别
 * false:子类不设置服务级别，继承父类的服务级别
 * SERVICE_DEGRADATION_NORMAL:正常服务
 * SERVICE_DEGRADATION_BASIC:直接返回
 * SERVICE_DEGRADATION_DISABLED:直接返回
 */
$action = new TestAction(SERVICE_DEGRADATION_DISABLED);
$action->safe_execute();


