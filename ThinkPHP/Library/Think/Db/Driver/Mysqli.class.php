<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Db\Driver;
use Think\Db;
defined('THINK_PATH') or exit();
/**
 * Mysqli数据库驱动类
 */
class Mysqli extends Db{

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config=''){
    	//extendsion_loaded——检查一个扩展是否已经加载
        if ( !extension_loaded('mysqli') ) {
            E(L('_NOT_SUPPERT_').':mysqli');
        }
        if(!empty($config)) {
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   '';
            }
        }
    }

    /**
     * 连接数据库方法
     * @access public
     * @throws ThinkExecption
     */
    public function connect($config='',$linkNum=0) {
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))  $config =   $this->config;
            $this->linkID[$linkNum] = new \mysqli($config['hostname'],$config['username'],$config['password'],$config['database'],$config['hostport']?intval($config['hostport']):3306);
            if (mysqli_connect_errno()) E(mysqli_connect_error());
            $dbVersion = $this->linkID[$linkNum]->server_version;
            
            // 设置数据库编码
            $this->linkID[$linkNum]->query("SET NAMES '".C('DB_CHARSET')."'");
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                $this->linkID[$linkNum]->query("SET sql_mode=''");
            }
            // 标记连接成功
            $this->connected    =   true;
            //注销数据库安全信息
            if(1 != C('DB_DEPLOY_TYPE')) unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     * 释放查询结果
     * 调用数据库驱动的free_result()，这里是mysqli。并设置$this->queryID为NULL.
     * @access public
     */
    public function free() {
    	//Mysqli的查询结果集是一个类，$this->queryID是mysqli_result的实例
        $this->queryID->free_result();
        $this->queryID = null;
    }

    /**
     * 执行查询 返回数据集
     * 1.初始化数据库连接
     * 2.释放前次的查询结果，即属性queryID，它是mysqli_result实例
     * 3.统计执行次数、记录开始执行时间
     * 4.执行mysqli的query()，并将结果集实例赋值给属性queryID
     * 5.debug>>>改进存储过程
     * 6.执行debug()，trace()执行的sql语句和所用时间
     * 7.如果sql出错则执行error()，来trac()sql语句和错误；否则，返回查询结果集中的记录数和得到的字段数
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function query($str) {
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        //统计执行次数
        N('db_query',1);
        // 记录开始执行时间
        G('queryStartTime');
        $this->queryID = $this->_linkID->query($str);	//queryID属性是在这里赋值的,是返回的结果集类实例
        // 对存储过程改进debug>>>这叫改进？
        if( $this->_linkID->more_results() ){
            while (($res = $this->_linkID->next_result()) != NULL) {
                $res->free_result();	//释放数据集所占有的内存
            }
        }
        $this->debug();
        if ( false === $this->queryID ) {
            $this->error();
            return false;
        } else {
            $this->numRows  = $this->queryID->num_rows;	//返回语句结果集中的函数
            $this->numCols    = $this->queryID->field_count;	//返回给定语句得到的字段数量
            return $this->getAll();
        }
    }

    /**
     * 执行语句
     * 1.初始化数据库连接
     * 2.释放上次的查询结果
     * 3.开始统计执行次数、记录开始执行时间
     * 4.执行mysqli扩展的query()，执行sql语句
     * 5.调用debug(),sql出错，如果开启了SQL日志记录，将执行的sql语句和时间拼接
     * 6.sql执行成功，赋值属性numRows和lastInsID,返回影响的记录数
     * 因为在sql执行成功后，调用了mysqli扩展的affected_rows()和insert_id()，所以，如果执行查询，会报错，因为它没有那两个方法
     * @access public
     * @param string $str  sql指令
     * @return integer
     */
    public function execute($str) {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;	//连接数据库失败
        $this->queryStr = $str;
        //释放前次的查询结果
        //统计执行次数
        if ( $this->queryID ) $this->free();
        N('db_write',1);
        // 记录开始执行时间
        G('queryStartTime');
        //$this->_linkID>>>mysqli的实例
        $result =   $this->_linkID->query($str);
        //如果开启了SQL日志记录，将执行的sql语句和时间拼接,使用trace()
        $this->debug();
        if ( false === $result ) {
        	//获取错误编号、错误信息、sql语句组成的数组，赋值给属性error
            $this->error();
            return false;
        } else {
            $this->numRows = $this->_linkID->affected_rows;	//查询是不能执行affected_rows()和insert_id()的，所以会报错
            $this->lastInsID = $this->_linkID->insert_id;
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {
        $this->initConnect(true);
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->_linkID->autocommit(false);
        }
        $this->transTimes++;
        return ;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->_linkID->autocommit( true);
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->_linkID->autocommit( true);
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * 通过msyqli_result的fetch_assoc()来获取数据，并重置queryID的指针位置，返回一个由结果组成的二维数组
     * 从mysqli_result类例中获取数据
     * @access private
     * @param string $sql  sql语句
     * @return array
     */
    private function getAll() {
        //返回数据集
        $result = array();
        if($this->numRows>0) {
            //返回数据集
            for($i=0;$i<$this->numRows ;$i++ ){
            	//queryID是mysqli_result实例
                $result[$i] = $this->queryID->fetch_assoc();	//获取一行结果记录作为关联数组
            }
            $this->queryID->data_seek(0);	//将指针指向结果集的任意行上。这里是重置指针
        }
        return $result;
    }

    /**
     * 取得数据表的字段信息
     * 1.通过mysqli扩展执行一个'showcolumns from tablename'的查询获取字段信息
     * 2.对获取到的列信息进行遍历，组成有字段名作为键和字段信息的关联数组组成的二维数组。
     * @access public
     * @return array
     */
    public function getFields($tableName) {
    	/*$result中是表中每个字段的信息的数组，其中:"Field">>>字段名，"Type">>>字段类型，"Null">>>是否为null,"Key">>>键，"Default">>>默认值,"Extra">>>额外说明，弱主键
    	 * array(12) {
			  [0]=>
			  array(6) {
			    ["Field"]=>
			    string(2) "id"
			    ["Type"]=>
			    string(16) "int(10) unsigned"
			    ["Null"]=>
			    string(2) "NO"
			    ["Key"]=>
			    string(3) "PRI"
			    ["Default"]=>
			    NULL
			    ["Extra"]=>
			    string(14) "auto_increment"
			  }
			  }
    	 */
        $result =   $this->query('SHOW COLUMNS FROM '.$this->parseKey($tableName));
        $info   =   array(); //要组成一个由字段名为键，字段信息为数组的二维数组。
        if($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],  //之所以还要加个name，是为了方便操作数据表的字段信息，而不是要用键名当作段名。
                    'type'    => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getTables($dbName='') {
        $sql    = !empty($dbName)?'SHOW TABLES FROM '.$dbName:'SHOW TABLES ';
        $result =   $this->query($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$key] = current($val);
            }
        }
        return $info;
    }

    /**
     * 替换记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @return false | integer
     */
    public function replace($data,$options=array()) {
        foreach ($data as $key=>$val){
            $value   =  $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[]   =  $value;
                $fields[]   =  $this->parseKey($key);
            }
        }
        $sql   =  'REPLACE INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        return $this->execute($sql);
    }

    /**
     * 插入记录
     * 将数据拼接成insert..value(),(),()……形式的字符串，并执行
     * 1.获取字段，并对字段进行检车
     * 2.遍历数据的二维数组，对值进行转换，过滤非标量，组成一个sql的value()形式的字符串
     * 3.将上一步的数组拼接成insert...value(),(),()形式字符串的sql语句
     * 4.执行sql语句
     * @access public
     * @param mixed $datas 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll($datas,$options=array(),$replace=false) {
        if(!is_array($datas[0])) return false;	//必须是一个二维数组
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'parseKey'));	//进行字段名和表名的检测，并没有具体意义
        $values  =  array();
        foreach ($datas as $data){
            $value   =  array();
            foreach ($data as $key=>$val){
            	//对字符串、布尔、null进行转化
                $val   =  $this->parseValue($val);
                if(is_scalar($val)) { // 过滤非标量数据
                    $value[]   =  $val;
                }
            }
            $values[]    = '('.implode(',', $value).')';
        }
        $sql   =  ($replace?'REPLACE':'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES '.implode(',',$values);
        return $this->execute($sql);
    }

    /**
     * 关闭数据库
     * @access public
     * @return volid
     */
    public function close() {
        if ($this->_linkID){
            $this->_linkID->close();
        }
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * 通过mysqli的errno和error属性获取错误编号和错误信息，拼接上查询语句，赋值给Mysqli的error
     * @static
     * @access public
     * @return string
     */
    public function error() {
        $this->error = $this->_linkID->errno.':'.$this->_linkID->error;
        if('' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        trace($this->error,'','ERR');
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @static
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        if($this->_linkID) {
            return  $this->_linkID->real_escape_string($str);
        }else{
            return addslashes($str);
        }
    }

    /**
     * 字段和表名处理添加`
     * 使用了trim，所以在输入字段名或表名的时候，不必要担心空格会造成影响。
     * 在正则表达式没有匹配到\',\",\*，\(\)`的时候，才在字段名或表名的首尾加上`
     * 用地址传递参数。
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        $key   =  trim($key);
        if(!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
           $key = '`'.$key.'`';
        }
        return $key;
    }
}
