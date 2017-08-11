<?php

require_once '../base/ActionBase.php';
require_once '../util/DelegatorUtil.php';

class DocParser {
    private $params;
    
    function parse($doc = '')
    {
        $this->params = array();
        if ($doc == '')
        {
            return $this->params;
        }
        
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment ) === false)
        {
            return $this->params;
        }
        $comment = trim($comment[1]);
        
        // Get all the lines and strip the * from the first character
        if (preg_match_all ( '#^\s*\*(.*)#m', $comment, $lines ) === false)
        {
            return $this->params;
        }
        
        $this->parseLines($lines [1]);
        
        return $this->params;
    }
    
    private function parseLines($lines)
    {
        foreach ($lines as $line)
        {
            $parsedLine = $this->parseLine($line);
            
            if ($parsedLine === false && !isset ($this->params ['description']))
            {
                if (isset($desc))
                {
                    // Store the first line in the short description
                    $this->params ['description'] = implode ( PHP_EOL, $desc );
                }
                $desc = array ();
            }
            elseif ($parsedLine !== false)
            {
                
                $desc [] = $parsedLine; // Store the line in the long description
            }
        }
        $desc = implode(' ', $desc);
        if (! empty ( $desc ))
        {
            $this->params ['long_description'] = $desc;
        }
    }
    
    private function parseLine($line)
    {
        $line = trim($line);
        
        if (empty($line))
        {
            return false;
        }
        
        if (strpos($line, '@') === 0)
        {
            if (strpos($line, ' ') > 0)
            {
                $param = substr($line, 1, strpos($line, ' ') - 1);
                $value = substr($line, strlen($param ) + 2);
            }
            else
            {
                $param = substr($line, 1);
                $value = '';
            }
            if ($this->setParam($param, $value))
            {
                return false;
            }
        }
        
        return $line;
    }
    
    private function setParam($param, $value)
    {
        $param = trim($param);
        if ($param == 'param' || $param == 'return')
        {
            $value = $this->formatParamOrReturn($value);
        }
        
        if ($param == 'param')
        {
            $this->params['param'][] = $value;
        }
        else
        {
            $this->params[$param] = $value;
        }
        return true;
    }
    
    private function formatParamOrReturn($string)
    {
        $string = trim($string);
        $pos = strpos($string, ' ');
        if ($pos > 0)
        {
            $type = substr($string, 0, $pos);
            return '(' . $type . ')' . substr($string, $pos + 1);
        }
        else
        {
            return $string;
        }
    }
}

class RestifyBase extends ActionBase
{
    private static $KEYS = array(
            'ec033cd69d3ccc9b8e728a2012bc673e',//key
    );
    private $dao;
    private $methods;
    private $dao_classname;

    function __construct($dao_classname)
    {
        parent::__construct();
        $this->dao = new $dao_classname();
        $this->methods = get_class_methods($this->dao);
        $this->dao_classname = $dao_classname;
    }

    protected function contact()
    {
        return WEIYUANKE_EMAIL;
    }

    protected function execute()
    {
        if (!is_object($this->dao))
        {
            return array(
                    "status" => ERROR_STATUS, "code" => ERROR_CODE,
                    'msg' => '初始化异常',
            );
        }

        //GET返回文档
        if ($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            $result = array("status" => SUCCESS_STATUS, "code" => SUCCESS_CODE);
            $result['result'] = array();
            
            $docparser = new DocParser();
            $reflection = new ReflectionClass ($this->dao_classname);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $mtd)
            {
                if ($mtd->getName() === '__construct')
                {
                    continue;
                }
                $method_config = array();
                $method_config['method_name'] = $mtd->getName();
                $info = $docparser->parse($mtd->getDocComment());  
                $method_config['method_doc'] = array();
                foreach (array('description', 'long_description', 'param', 'return') as $fld)
                {
                    if (isset($info[$fld]))
                    {
                        $method_config['method_doc'][$fld] = $info[$fld];
                    }
                }      
                
                $method_config['params'] = array();
                foreach ($mtd->getParameters() as $para)
                {
                    if ($para->isDefaultValueAvailable())
                    {
                        $method_config['params'][$para->getName()] = 'default value:'.strval($para->getDefaultValue());
                    }
                    else
                    {
                        $method_config['params'][$para->getName()] = 'required';
                    }
                }
                
                $result['result'][] = $method_config;
            }
            
            return $result;
        }
        

        //处理restdaopoststr(urlencode之后)
        if (isset($_REQUEST['restdaopoststr']))
        {
            $request_params = json_decode(urldecode($_REQUEST['restdaopoststr']), true);
            if (is_array($request_params))
            {
                $_REQUEST = $request_params;
            }
        }

        //权限
        if (!isset($_REQUEST['key']) || !in_array($_REQUEST['key'], self::$KEYS))
        {
            return array(
                    'status' => ERROR_STATUS, 'code' => ERROR_CODE_PERMISSION_DENIED,
                    'reason' => ERROR_MSG_PERMISSION_DENIED
            );
        }

        if (!isset($_REQUEST['op']) || !in_array($_REQUEST['op'], $this->methods))
        {
            return array(
                    "status" => ERROR_STATUS, "code" => ERROR_CODE_PARAM,
                    'msg' => '没有指定方法或不支持的方法'
            );
        }

        $target_method = $_REQUEST['op'];
        $ReflectionMethod =  new ReflectionMethod($this->dao, $target_method);
        $method_params = array();
        $params = $ReflectionMethod->getParameters();
        foreach ($params as $param)
        {
            $temp = array();
            $temp['name'] = $param->name;
            if ($param->isOptional())
            {
                $temp['defaultvalue'] = $param->getDefaultValue();
            }
            $method_params[] = $temp;
        }
        
        //param
        $call_params = array();
        foreach ($method_params as $param)
        {
            if (isset($_REQUEST[$param['name']]))
            {
                $call_params[] = $_REQUEST[$param['name']];
            }
            else
            {
                if (array_key_exists('defaultvalue', $param))
                {
                    $call_params[] = $param['defaultvalue'];
                }
                else
                {
                    return array(
                            "status" => ERROR_STATUS, "code" => ERROR_CODE_PARAM,
                            "msg" => array(
                                    'msg' => '缺少参数'.$param['name'],
                                    'method_name' => $target_method,
                                    'method_params' => $method_params,
                            ),
                    );
                }
            }
        }

        //方法调用
        $call_result = $ReflectionMethod->invokeArgs($this->dao, $call_params);

        return array(
                "status" => SUCCESS_STATUS, "code" => SUCCESS_CODE,
                'result' => $call_result,
        );
    }
}
