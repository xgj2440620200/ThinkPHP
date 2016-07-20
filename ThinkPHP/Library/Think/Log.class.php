<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * 日志处理类
 */
class Log {

    // 日志级别 从上到下，由低到高
    const EMERG     = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT     = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT      = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR       = 'ERR';  // 一般错误: 一般性错误
    const WARN      = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE    = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO      = 'INFO';  // 信息: 程序输出信息
    const DEBUG     = 'DEBUG';  // 调试: 调试信息
    const SQL       = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志信息
    static protected $log       =  array();

    // 日志存储
    static protected $storage   =   null;

    // 日志初始化。实例化日志中的某个驱动类。
    static public function init($config=array()){	//使用array()作为参数，可以方便的配置很多参数。
    	//日志默认的记录方式是文件。
        $type   =   isset($config['type'])?$config['type']:'File';
        // $type = isset($config['type']) ? : 'File';
        /* ucwords——将字符串中每个单词的首字母转换为大写
         * 这里单词的定义是紧跟在空白字符（空格符、制表符、换行符、回车符、水平线以及竖线）只有的子字符串。
         */
        $class  =   strpos($type,'\\')? $type: 'Think\\Log\\Driver\\'. ucwords(strtolower($type));//可以通过在'Think\\Log\\Driver\\'中添加类文件来进行扩展。           
        unset($config['type']);	//变量使用后就销毁。
        self::$storage = new $class($config);	//这里使用的是使用命名空间。并没有去获取那个类文件。
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * 将日志信息保存在静态变量一维数组$log中，即内存中。
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     * @return void
     */
    static function record($message,$level=self::ERR,$record=false) {	//默认级别的ERR。
        if($record || false !== strpos(C('LOG_LEVEL'),$level)) {
            self::$log[] =   "{$level}: {$message}\r\n";	//debug>>>有'\n'为什么还要用'\r'?如果不用花括号会是什么结果？
            //mine>>>self::$log[] = "{$level}: {$message}\n";
        }
    }

    /**
     * 日志保存
     * 调用了Log类的write()保存日志，还有个关键操作是使用赋值空数组来情况了日志缓存。
     * @static
     * @access public
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    static function save($type='',$destination='') {
        if(empty(self::$log)) return ;

        if(empty($destination))
            $destination = C('LOG_PATH').date('y_m_d').'.log';
        if(!self::$storage){
            $type = $type?$type:C('LOG_TYPE');
            //mine>>>$type = $type ? : C('LOG_TYPE');
            $class  =   'Think\\Log\\Driver\\'. ucwords($type);
            self::$storage = new $class();            
        }
        $message    =   implode('',self::$log);	//日志信息中自带了'\n'，所以在这里不用换行了。
        self::$storage->write($message,$destination);
        // 保存后清空日志缓存
        self::$log = array();	//如果没有这个操作，系统分配给php的内存会很快用完。
    }

    /**
     * 日志直接写入
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    static function write($message,$level=self::ERR,$type='',$destination='') {	
        if(!self::$storage){
            $type = $type?$type:C('LOG_TYPE');
            $class  =   'Think\\Log\\Driver\\'. ucwords($type);
            self::$storage = new $class();            
        }
        if(empty($destination))
            $destination = C('LOG_PATH').date('y_m_d').'.log';        
        self::$storage->write("{$level}: {$message}", $destination);
    }
}