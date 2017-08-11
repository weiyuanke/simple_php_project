<?php
/*** response message ***/
define('SUCCESS_STATUS',                        'success');
define('ERROR_STATUS',                          'failed');
define('ERROR_MSG_NOT_A_JSON',                  '非json格式');
define('ERROR_MSG_MYSQL_CONNECT',               '无法连接mysql服务器[%s]');

/*** response code ***/
define('SUCCESS_CODE',                          0);
define('ERROR_CODE',                            -1);
define('ERROR_CODE_NOT_A_JSON',                 13);

/*** email ***/
define('WEIYUANKE_EMAIL',                       'weiyuanke123@gmail.com');

/***服务降级相关***/
define('SERVICE_DEGRADATION_NORMAL',            'degradation_normal');//正常
define('SERVICE_DEGRADATION_BASIC',             'degradation_basic');//提供基本服务
define('SERVICE_DEGRADATION_DISABLED',          'degradation_disabled');//禁用服务（如果可以的话）

/***数据库相关***/
define('MYSQL_HOST',                            'weiyuanke.cloudapp.net:3306');
define('MYSQL_USER',                            'root');
define('MYSQL_PWD',                             'weiyuanke');

/***缓存相关***/
define('MEMCACHE_HOST',                         'weiyuanke.cloudapp.net:11211');
define('MEMCACHE_PORT',                         11212);

