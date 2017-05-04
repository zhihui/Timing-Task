<?php
class Database {
    
    /**
     * PDO
     * 
     * @var \PDO
     */
    protected $pdo;
    
    protected $bindParams;
    
    protected $bindMode;
    
    protected $error = array("code" => "00000", "info" => array("00000", NULL, NULL));
    
    /**
     * Database
     *
     * @param string $host     主机
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $dbname   数据库
     * @param string $charset  字符集
     * @param number $pconnect 是否长连接
     */
    public function __construct($host, $username, $password, $dbname, $charset, $pconnect = 0){
        $dsn = "mysql:host=$host;dbname=$dbname";
        $this->pdo = new \PDO($dsn, $username, $password);
        $this->pdo->setAttribute(\PDO::ATTR_PERSISTENT, TRUE);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("SET NAMES " . $charset);
    }
    
    public function lastInsertId($name = NULL){
        return $this->pdo->lastInsertId($name);
    }
    
    /**
     * 执行SQL语句
     * 
     * @param string $sql SQL语句
     * @return number 影响的行数
     */
    public function exec($sql){
        $ret = $this->pdo->exec($sql);
        $this->updateError($this->pdo->errorCode(), $this->pdo->errorInfo());
        return $ret;
    }
    
    /**
     * 错误代码
     * 
     * @return string
     */
    public function errorCode(){
        return $this->error["code"];
    }
    
    /**
     * 错误信息
     * 
     * @return array
     */
    public function errorInfo(){
        return $this->error["info"];
    }
    
    /**
     * 更新错误信息
     * 
     * @param string $code 错误代码
     * @param string $info 错误信息
     * @return void
     */
    protected function updateError($code, $info){
        $this->error["code"] = $code;
        $this->error["info"] = $info;
    }
    
    /**
     * 查询数据
     * 
     * @param string $sql    SQL语句
     * @param array  $params 绑定参数
     * @return \Framework\DB\DatabaseQuery
     */
    public function query($sql, array $params = NULL){
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return new DatabaseQuery($stmt);
    }
    
    /**
     * 插入数据
     * 
     * @param string $table 表名称
     * @param array  $data  要插入的数据
     * @return boolean 是否成功
     */
    public function insert($table, array $data){
        $keys = array_keys($data);
        $vals = array_values($data);
        $marks = array();
        foreach ($keys as $i => $v){
            $keys[$i] = '`' . $v . '`';
            $marks[] = '?';
        }
        
        $sql = "INSERT INTO `{$table}` (" . implode(",", $keys) . ") VALUES(" . implode(",", $marks) . ");";
        $stmt = $this->pdo->prepare($sql);
        $bool = $stmt->execute($vals);
        $this->updateError($stmt->errorCode(), $stmt->errorInfo());
        if($bool){
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * 更新数据
     * 
     * @param string $table  表名称
     * @param array  $data   要更新的数据
     * @param string $where  条件
     * @param array  $params 绑定参数
     * @return number|boolean 成功时返回影响的条数，错误时返回FALSE
     */
    public function update($table, array $data, $where, array $params){
        $keys = array_keys($data);
        $vals = array_values($data);
        $sets = array();
        foreach ($keys as $i => $key){
            $sets[] = "`$key`=?";
        }
        
        foreach ($params as $value){
            $vals[] = $value;
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(",", $sets) . " WHERE " . $where;
        $stmt = $this->pdo->prepare($sql);
        $bool = $stmt->execute($vals);
        $this->updateError($stmt->errorCode(), $stmt->errorInfo());
        if($bool){
            return $stmt->rowCount();
        }
        
        return FALSE;
    }
    
    /**
     * 删除数据
     * 
     * @param string $table  表名称
     * @param string $where  条件
     * @param array  $params 绑定参数
     * @return number|boolean 成功时返回影响的条数，错误时返回FALSE
     */
    public function delete($table, $where, array $params){
        $sql = "DELETE FROM `{$table}` WHERE " . $where;
        $stmt = $this->pdo->prepare($sql);
        $bool = $stmt->execute($params);
        $this->updateError($stmt->errorCode(), $stmt->errorInfo());
        if($bool){
            return $stmt->rowCount();
        }
        
        return FALSE;
    }
    
    protected function quote($str){
        return $this->pdo->quote($str);
    }
}