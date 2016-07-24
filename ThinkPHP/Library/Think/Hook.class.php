<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP系统钩子实现
 */
class Hook {

    static private  $tags       =   array();

    /**
     * 动态添加插件到某个标签
     * @param string $tag 标签名称
     * @param mixed $name 插件名称
     * @return void
     */
    static public function add($tag,$name) {
        if(!isset(self::$tags[$tag])){
            self::$tags[$tag]   =   array();
        }
        if(is_array($name)){
            self::$tags[$tag]   =   array_merge(self::$tags[$tag],$name);
        }else{
            self::$tags[$tag][] =   $name;
        }
    }

    /**
     * 批量导入插件
     * 实际上是给属性$tags添加标签单元，每个标签是以标签位置为键名，行为类命名空间为单元组成的数组。
     * @param array $data 插件信息
     * @param boolean $recursive 是否递归合并
     * @return void
     */
    static public function import($data,$recursive=true) {
        if(!$recursive){ // 覆盖导入
            self::$tags   =   array_merge(self::$tags,$data);
        }else{ // 合并导入
            foreach ($data as $tag=>$val){
            		/*
            		 * $tag>>>标签位置
            		 * $val>>>对应的行为类的命名空间
            		 */
                if(!isset(self::$tags[$tag]))
                	/*self::$tags追加的标签有>>>'
                	 * app_begin
                	 * app_end
                	 * view_parse
                	 * template_filter
                	 * view_filter
                	 * app_init 
                	 */
                    self::$tags[$tag]   =   array();            
                if(!empty($val['_overlay'])){
                    // 可以针对某个标签指定覆盖模式
                    unset($val['_overlay']);
                    self::$tags[$tag]   =   $val;
                }else{
                    // 合并模式
                    self::$tags[$tag]   =   array_merge(self::$tags[$tag],$val);
                }
            }
            /*
             * $tags>>>
             * 	array(5) {
				  ["app_begin"]=>
				  array(1) {
				    [0]=>
				    string(22) "Behavior\ReadHtmlCache"
				  }
				  ["app_end"]=>
				  array(1) {
				    [0]=>
				    string(22) "Behavior\ShowPageTrace"
				  }
				  ["view_parse"]=>
				  array(1) {
				    [0]=>
				    string(22) "Behavior\ParseTemplate"
				  }
				  ["template_filter"]=>
				  array(1) {
				    [0]=>
				    string(23) "Behavior\ContentReplace"
				  }
				  ["view_filter"]=>
				  array(1) {
				    [0]=>
				    string(23) "Behavior\WriteHtmlCache"
				  }
				}
             */
        }
    }

    /**
     * 获取插件信息
     * @param string $tag 插件位置 留空获取全部
     * @return array
     */
    static public function get($tag='') {
        if(empty($tag)){
            // 获取全部的插件信息
            return self::$tags;
        }else{
            return self::$tags[$tag];
        }
    }

    /**
     * 监听标签的插件
     * @param string $tag 标签名称
     * @param mixed $params 传入参数
     * @return void
     */
    static public function listen($tag, &$params=NULL) {
    		if($tag == 'view_parse'){
    			if(isset(self::$tags[$tag])){
    				if(APP_DEBUG){
    					G($tag.'Start');
    					trace('['.$tag.']--START--','','INFO');
    				}
    				foreach(self::$tags[$tag] as $name){
    					APP_DEBUG && G($name.'_start');
    					//$name>>>'Behavior\ParseTemplate'
    					//$tag>>>'view_parse';
    					$result = self::exec($name, $tag, $params);
    				}
    			}
    		} 
    		//$params>>>NULL
    		//$tag>>>'app_init'
        if(isset(self::$tags[$tag])) {
            if(APP_DEBUG) {
                G($tag.'Start');
                trace('[ '.$tag.' ] --START--','','INFO');
            }
            //self::$tags[$tag]>>>array('Common\Behavior\InitHook')
            foreach (self::$tags[$tag] as $name) {
                APP_DEBUG && G($name.'_start');
                //$name>>>'Common\Behavior\InitHook'
                $result =   self::exec($name, $tag,$params);
                if(APP_DEBUG){
                    G($name.'_end');
                    trace('Run '.$name.' [ RunTime:'.G($name.'_start',$name.'_end',6).'s ]','','INFO');
                }
                if(false === $result) {
                    // 如果返回false 则中断插件执行
                    return ;
                }
            }
            if(APP_DEBUG) { // 记录行为的执行日志
                trace('[ '.$tag.' ] --END-- [ RunTime:'.G($tag.'Start',$tag.'End',6).'s ]','','INFO');
            }
        }
        return;
    }

    /**
     * 执行某个插件
     * 就是是实例化某个行为类,并调用$tag对应的方法。如果$name中没有出现'\',就默认$tag为'run'
     * @param string $name 插件名称
     * @param string $tag 方法名（标签名）     
     * @param Mixed $params 传入的参数
     * @return void
     */
    static public function exec($name, $tag,&$params=NULL) {
    		//$name>>>'Common\Behavior\InitHook'
    		//$tag>>>'app_init'
        if(false === strpos($name,'\\')) {
            // 插件（多个入口）
            $class   =  "Addons\\{$name}\\{$name}Addon";
        }else{
            // 行为扩展（只有一个run入口方法）
            $class   =  $name.'Behavior';
            $tag    =   'run';
        }
        //$addon>>>object(Common\Behavior\InitHookBehavior)
        $addon   = new $class();
        return $addon->$tag($params);
    }
}
