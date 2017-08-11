<?php

require_once '../config.php';
require_once '../global.php';

interface Action
{
    function safe_execute();
}

abstract class ActionBase implements Action
{
    protected $result;
    public $degradation_level = false;
    public static $ALL_SERVICE_LEVELS = array(
            SERVICE_DEGRADATION_BASIC,      //正常(默认)
            SERVICE_DEGRADATION_NORMAL,     //提供基本服务
            SERVICE_DEGRADATION_DISABLED    //禁用服务（如果可以的话）
    );

    function __construct()
    {
        $this->result = NULL;
        $this->degradation_level = SERVICE_DEGRADATION_NORMAL;
    }

    function safe_execute()
    {
        $response = '';
        try
        {
            try
            {
                $response = $this->execute();
            }
            catch (Exception $exc)
            {
                throw new Exception($exc->getMessage(), $exc->getCode());
            }

            if (is_array($response))
            {
                $this->result = $response;
            }
            else
            {
                $this->result = json_decode(strval($response), true);
                if ($this->result === NULL)
                {
                    throw new Exception(ERROR_MSG_NOT_A_JSON, ERROR_CODE_NOT_A_JSON);
                }
            }
        }
        catch (Exception $exc)
        {
            $this->result = array(
                'status' => ERROR_STATUS, 'reason' => $exc->getMessage(), 'code' => $exc->getCode()
            );
        }
        
        $response = json_encode($this->result, true);
        header('Content-Length: ' . strlen($response));
        echo $response;
    }

    abstract protected function contact();
    abstract protected function execute();
}

/*** init ***/
ini_timezone();
ini_error_handler();
ini_post();
ini_header();
