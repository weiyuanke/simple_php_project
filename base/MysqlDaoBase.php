<?php

require_once '../config.php';
require_once '../global.php';

abstract class MysqlDaoBase
{
    protected static $RANGE_OPS = array(
        '$gt' => '>', '$gte' => '>=', '$lt' => '<', '$lte' => '<=', '$ne' => '<>'
    );

    private $conn = false;
    private $host = false;
    private $connect_timeout = false;
    private $in_transaction  = false;
    
    function __construct()
    {
        $host = explode(',', $this->host());
        $this->host = $host[rand(0, count($host)-1)];
        $this->connect_timeout = $this->connect_timeout();
    }
    
    function __destruct()
    {
        if ($this->conn !== false)
        {
            $this->conn->close();
            $this->conn = false;
            $this->host = false;
            $this->connect_timeout = false;
            $this->in_transaction = false;
        }
    }
    
    abstract protected function host();

    abstract protected function user();
    
    abstract protected function passwd();

    abstract protected function database();

    protected function connect_timeout()
    {
        return 5;
    }

    protected function read_timeout()
    {
        return 10;
    }

    protected function write_timeout()
    {
        return 10;
    }

    protected function lock_wait_timeout()
    {
        return 10;
    }

    protected function options()
    {
        return array();
    }

    protected function ensure_connect()
    {
        if ($this->conn === false)
        {
            list($host, $port) = explode(':', $this->host);

            $this->conn = mysqli_init();
            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->connect_timeout);
            $this->conn->options(MYSQLI_OPT_READ_TIMEOUT, $this->read_timeout());
            $this->conn->options(MYSQLI_OPT_WRITE_TIMEOUT, $this->write_timeout());
            $options = $this->options();
            if( !empty($options) )
            {
                foreach ($options as $key => $value)
                {
                    $this->conn->options($key, $value);
                }
            }
            $this->conn->real_connect($host, $this->user(), $this->passwd(), $this->database(), intval($port));

            if($this->conn->connect_errno)
            {
                error_log('mysql connect: errno is ' . $this->conn->connect_errno . ' and error is ' . $this->conn->connect_error);
                $this->conn = false;
            }
            else
            {
                $this->conn->query('set character_set_client=utf8mb4');
                $this->conn->query('set character_set_results=utf8mb4');
                $this->conn->query('set character_set_connection=utf8mb4');
                $this->conn->query('set innodb_lock_wait_timeout=' . $this->lock_wait_timeout());
            }
        }
        
        //失败时抛异常
        if ($this->conn === false)
        {
            throw new Exception(sprintf(ERROR_MSG_MYSQL_CONNECT, $this->host), ERROR_CODE_MYSQL);
        }
    }
    
    /**
     * 开启事务,不支持事务嵌套
     * http://php.net/manual/zh/mysqli.begin-transaction.php
     * @return   bool
     */
    protected function begin_transaction($flag=MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)
    {
        if( $this->in_transaction !== false )
        {
            throw new Exception("PHP mysqli error : already in transaction", ERROR_CODE);
            
        }
        $this->ensure_connect();
        $this->in_transaction = true;
        return $this->conn->begin_transaction($flag);
    }
    
    /**
    * 提交事务,不支持事务嵌套
    * @return   bool
    **/
    public function commit()
    {
        if( $this->in_transaction === false )
        {
            throw new Exception("PHP mysqli error : no transaction begin", ERROR_CODE);
            
        }
        $this->ensure_connect();
        if( !$this->conn->commit() )
        {
            $this->in_transaction = false;
            $this->conn->rollBack();
            return false;
        }
        $this->in_transaction = false;
        return true;
    }
    
    /**
    * 回滚事务,不支持事务嵌套
    * @return   bool
    **/
    public function roll_back()
    {
        $this->ensure_connect();
        $this->in_transaction = false;
        return $this->conn->rollBack();
    }

    private function condition_string($data, $conn)
    {
        if (is_array($data))
        {
            $buffer = '';

            foreach ($data as $key => $value)
            {
                if ($value === NULL)
                {
                    $buffer .= '`' . $key . '` is null and ';
                }
                else if (is_int($value))
                {
                    $buffer .= '`' . $key . '`=' . intval($value) . ' and ';
                }
                else if (is_array($value))
                {
                    $range_query = false;

                    foreach (self::$RANGE_OPS as $op => $sign)
                    {
                        if (isset($value[$op]))
                        {
                            $range_query = true;

                            if (is_int($value[$op]))
                            {
                                $buffer .= '`' . $key . '`' . $sign . intval($value[$op]) . ' and ';
                            }
                            else
                            {
                                $buffer .= '`' . $key . '`' . $sign . '"' . $conn->real_escape_string(strval($value[$op])) . '" and ';
                            }
                        }
                    }

                    if (isset($value['$match']))
                    {
                        $range_query = true;

                        $buffer .= 'match(`' . $key . '`)against("' . $conn->real_escape_string(strval($value['$match'])) . '") and ';
                    }

                    if (isset($value['$modula']))
                    {
                        $range_query = true;

                        foreach ($value['$modula'] as $modula => $bucket);

                        $buffer .= sprintf('`%s` %% %d = %d and ', $key, intval($modula), intval($bucket));
                    }

                    if (!$range_query)
                    {
                        $buffer .= '`' . $key . '` in ("' . implode('","', $value) . '") and ';
                    }
                }
                else
                {
                    $buffer .= '`' . $key . '`="' . $conn->real_escape_string(strval($value)) . '" and ';
                }
            }

            return rcut($buffer, ' and ');
        }
        else if (is_string($data))
        {
            return $data;
        }
        else
        {
            throw new Exception(sprintf(ERROR_MSG_MYSQL_PARSE, strval($data)), ERROR_CODE_MYSQL);
        }
    }
    
    private function operation_string($data, $conn)
    {
        if (is_array($data))
        {
            $buffer = '';

            foreach ($data as $key => $value)
            {
                if (is_int($value))
                {
                    $buffer .= '`' . $key . '`=' . intval($value) . ' , ';
                }
                else if ($value !== NULL)
                {
                    $buffer .= '`' . $key . '`="' . $conn->real_escape_string(strval($value)) . '" , ';
                }
                else
                {
                    $buffer .= '`' . $key . '`=NULL , ';
                }
            }

            return rcut($buffer, ' , ');
        }
        else if (is_string($data))
        {
            return $data;
        }
        else
        {
            throw new Exception(sprintf(ERROR_MSG_MYSQL_PARSE, strval($data)), ERROR_CODE_MYSQL);
        }
    }

    /**
     * 删除
     * @param string $table
     * @param array $query
     * @return int
     */
    public function delete($table, $query)
    {
        $this->ensure_connect();
        $sql_query = 'delete from `' . $table . '` where ' . $this->condition_string($query, $this->conn);
        $result = $this->conn->query($sql_query);
        return $result;
    }
    
    /**
     * 插入，返回插入的自增ID
     * @param string $table
     * @param array $insert
     * @return boolean
     */
    public function insert($table, $insert)
    {
        $this->ensure_connect();
        $sql_query  = 'insert `' . $table . '` set ' . $this->operation_string($insert, $this->conn);
        $result = $this->conn->query($sql_query);
        return $result === false ? false : $this->conn->insert_id;
    }
    
    /**
     * 更新数据库 返回更新的行数
     *
     * @param      $table
     * @param      $query
     * @param      $update
     * @return mixed
     * @throws Exception
     */
    public function update($table, $query, $update, &$affected_rows = false)
    {        
        $this->ensure_connect();
        $sql_query = 'update `' . $table . '` set ' . $this->operation_string($update, $this->conn)
        . ' where ' . $this->condition_string($query, $this->conn);
        $result = $this->conn->query($sql_query);
        return $this->conn->affected_rows;
    }
    
    /**
     * 查询
     * @param string $table 数据库表名
     * @param array $query 查询条件
     * @param array $fields 返回字段, array('*')表示所有字段
     * @param string $orders
     * @param string $limit
     * @param string $hints
     * @param string $group
     * @throws Exception
     * @return array
     */
    public function select($table, $query, $fields, $orders = false, $limit = false, $hints = false, $group = false)
    {
        $this->ensure_connect();
        
        $sql_query = 'select ' . implode(',', $fields) . ' from `' . $table . '`';
        
        if (!empty($hints))
        {
            $buffer = '';
            
            foreach ($hints as $key)
            {
                $buffer .= '`' . $key . '`, ';
            }
            
            $sql_query .= ' use index (' . rcut($buffer, ', ') . ')';
        }
        
        $sql_query = $sql_query . ' where ' . $this->condition_string($query, $this->conn);
        
        if (!empty($orders))
        {
            $buffer = '';
            
            foreach ($orders as $key => $value)
            {
                $buffer .= '`' . $key . '` ' . $value . ', ';
            }
            
            $sql_query .= ' order by ' . rcut($buffer, ', ');
        }
        
        if ($group !== false)
        {
            $sql_query .= ' group by ' . implode(',', $group);
        }
        
        if ($limit !== false)
        {
            if (is_array($limit))
            {
                $sql_query .= ' limit ' . implode(',', $limit);
            }
            else
            {
                $sql_query .= ' limit ' . intval($limit);
            }
        }
        
        $cursor = $this->conn->query($sql_query);
        
        $result = array();
        
        if ($cursor !== false)
        {
            while($row = $cursor->fetch_assoc())
            {
                $result[] = $row;
            }
            
            $cursor->free();
        }
        
        return $result;
    }
    
    /**
     * 获取给定数据库下，数据表列表
     * @return array
     */
    public function get_datatable_names()
    {
        $this->ensure_connect();
        $sql_query = 'show tables;';
        $cursor = $this->conn->query($sql_query);
        
        $result = array();
        if ($cursor !== false)
        {
            while($row = $cursor->fetch_assoc())
            {
                $result[] = array_values($row)[0];
            }
            $cursor->free();
        }
        
        return $result;
    }
}
