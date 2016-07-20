<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
// 分布式文件存储类
class Storage {

    /**
     * 操作句柄
     * @var string
     * @access protected
     */
    static protected $handler    ;

    /**
     * 连接分布式文件系统
     * @access public
     * @param string $type 文件类型
     * @param array $options  配置数组
     * @return void
     */
    static public function connect($type,$options=array()) {
        $class  =   'Think\\Storage\\Driver\\'.ucwords($type);
        self::$handler = new $class($options);
    }

    static public function __callstatic($method,$args){
    	/*
    	 * $method——'has'
    	 * $args——array('./Runtime/common~runtime.php')
    	 */
    	//end——将数组的内部指针指向最后一个单元。
    	//$type——'./Runtime/common~runtime.php'
        $type=end($args);
        //ucfirst——将字符串的首字符转换为大写
        $method_type=$method.ucfirst($type);
        if(method_exists(self::$handler, $method_type)){
           /*
            * call_user_func_array——调用回调函数，并把一个数组参数作为回调函数的参数。
            */
           return call_user_func_array(array(self::$handler,$method_type), $args);
        }
        //调用缓存类型自己的方法
        if(method_exists(self::$handler, $method)){
           return call_user_func_array(array(self::$handler,$method), $args);
        }
    }
}
