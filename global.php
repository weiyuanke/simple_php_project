<?php

/**
 * 根据curl mutil调用多个方法时，不同方法的retieve key
 * @param array $method
 * @return string
 */
function get_method_retrieve_key($method)
{
    return $method[0].'.'.crc32(json_encode($method[1]));
}

/**
 * 初始化post数据
 */
function ini_post()
{
    $post_data = file_get_contents("php://input");
    if ($post_data != '')
    {
        //check gzip header
        if (strlen($post_data) > 1 && $post_data[0] == "\x1f" && $post_data[1] == "\x8b")
        {
            if (strlen($post_data) > 18)
            {
                $post_data_bak = $post_data;
                // Replace for previous hack, refer to http://www.php.net/manual/en/function.gzdecode.php
                $post_data = function_exists('gzdecode') ? gzdecode($post_data) : gzinflate(substr($post_data, 10, -8));
                if ($post_data === false && $_SERVER['REQUEST_TIME'] % 100 < 1)
                {
                    $pmsg = array("tag"=>"post_data decode error", "msg" => base64_encode($post_data_bak));
                    error_log(json_encode($pmsg));
                }
            }
            else
            {
                // invalid gzip data
                return;
            }
        }
        if ($post_data[0] == '{' && $post_data[strlen($post_data) - 1] == '}' ||
            $post_data[0] == '[' && $post_data[strlen($post_data) - 1] == ']')
        {
            $json_post_data = json_decode($post_data, true);
            if ($json_post_data !== NULL)
            {
                $_POST = $json_post_data;
            }
        }
        $_REQUEST = array_merge($_REQUEST, $_POST);
    }
}

function ini_header()
{
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: application/json; charset=utf-8');
    header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
    header('Pragma: no-cache');
}

function ini_timezone()
{
    date_default_timezone_set('Asia/Shanghai');
}

function ini_error_handler()
{
    set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        if ($errno === E_WARNING || $errno === E_NOTICE)
        {
            $GLOBALS['ERROR_MESSAGE'] = $errstr;
            error_log(json_encode($errcontext, JSON_UNESCAPED_UNICODE));
        }
        return false;
    });
}

function access_log()
{
    $url    = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
    $query  = http_build_query($_GET, '', '&');
    $postdata = json_encode($_POST);
    $agent  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-';
    $cookie = isset($_COOKIE['JSESSIONID']) ? $_COOKIE['JSESSIONID'] : '-';
    
    return sprintf('%s?%s "%s" "%s" "%s" "%s"', $url, $query, $postdata, $agent, ip(), $cookie);
}