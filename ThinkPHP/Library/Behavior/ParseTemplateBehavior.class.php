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
namespace Behavior;
use Think\Storage;
use Think\Think;
/**
 * 系统行为扩展：模板解析
 */
class ParseTemplateBehavior {

    // 行为扩展的执行入口必须是run
    public function run(&$_data){
    		//C('TMPL_ENGINE_TYPE')——'Think'
    		/*
    		 * $_data>>>
    		 * 	array(4) {
			  ["var"]=>
			  array(3) {
			    ["category"]=>
			    array(1) {
			      [0]=>
			      array(27) {
			        ["id"]=>
			        string(1) "1"
			        ["name"]=>
			        string(4) "blog"
			        ["title"]=>
			        string(6) "博客"
			        ["pid"]=>
			        string(1) "0"
			        ["sort"]=>
			        string(1) "0"
			        ["list_row"]=>
			        string(2) "10"
			        ["meta_title"]=>
			        string(0) ""
			        ["keywords"]=>
			        string(0) ""
			        ["description"]=>
			        string(0) ""
			        ["template_index"]=>
			        string(0) ""
			        ["template_lists"]=>
			        string(0) ""
			        ["template_detail"]=>
			        string(0) ""
			        ["template_edit"]=>
			        string(0) ""
			        ["model"]=>
			        string(1) "2"
			        ["type"]=>
			        string(3) "2,1"
			        ["link_id"]=>
			        string(1) "0"
			        ["allow_publish"]=>
			        string(1) "0"
			        ["display"]=>
			        string(1) "1"
			        ["reply"]=>
			        string(1) "0"
			        ["check"]=>
			        string(1) "0"
			        ["reply_model"]=>
			        string(1) "1"
			        ["extend"]=>
			        string(0) ""
			        ["create_time"]=>
			        string(10) "1379474947"
			        ["update_time"]=>
			        string(10) "1382701539"
			        ["status"]=>
			        string(1) "1"
			        ["icon"]=>
			        string(1) "0"
			        ["_"]=>
			        array(1) {
			          [0]=>
			          array(26) {
			            ["id"]=>
			            string(1) "2"
			            ["name"]=>
			            string(12) "default_blog"
			            ["title"]=>
			            string(12) "默认分类"
			            ["pid"]=>
			            string(1) "1"
			            ["sort"]=>
			            string(1) "1"
			            ["list_row"]=>
			            string(2) "10"
			            ["meta_title"]=>
			            string(0) ""
			            ["keywords"]=>
			            string(0) ""
			            ["description"]=>
			            string(0) ""
			            ["template_index"]=>
			            string(0) ""
			            ["template_lists"]=>
			            string(0) ""
			            ["template_detail"]=>
			            string(0) ""
			            ["template_edit"]=>
			            string(0) ""
			            ["model"]=>
			            string(1) "2"
			            ["type"]=>
			            string(5) "2,1,3"
			            ["link_id"]=>
			            string(1) "0"
			            ["allow_publish"]=>
			            string(1) "1"
			            ["display"]=>
			            string(1) "1"
			            ["reply"]=>
			            string(1) "0"
			            ["check"]=>
			            string(1) "1"
			            ["reply_model"]=>
			            string(1) "1"
			            ["extend"]=>
			            string(0) ""
			            ["create_time"]=>
			            string(10) "1379475028"
			            ["update_time"]=>
			            string(10) "1386839751"
			            ["status"]=>
			            string(1) "1"
			            ["icon"]=>
			            string(2) "31"
			          }
			        }
			      }
			    }
			    ["lists"]=>
			    array(1) {
			      [0]=>
			      array(23) {
			        ["id"]=>
			        string(1) "1"
			        ["uid"]=>
			        string(1) "1"
			        ["name"]=>
			        string(0) ""
			        ["title"]=>
			        string(26) "OneThink1.0正式版发布"
			        ["category_id"]=>
			        string(1) "2"
			        ["description"]=>
			        string(38) "大家期待的OneThink正式版发布"
			        ["root"]=>
			        string(1) "0"
			        ["pid"]=>
			        string(1) "0"
			        ["model_id"]=>
			        string(1) "2"
			        ["type"]=>
			        string(1) "2"
			        ["position"]=>
			        string(1) "0"
			        ["link_id"]=>
			        string(1) "0"
			        ["cover_id"]=>
			        string(1) "0"
			        ["display"]=>
			        string(1) "1"
			        ["deadline"]=>
			        string(1) "0"
			        ["attach"]=>
			        string(1) "0"
			        ["view"]=>
			        string(1) "9"
			        ["comment"]=>
			        string(1) "0"
			        ["extend"]=>
			        string(1) "0"
			        ["level"]=>
			        string(1) "0"
			        ["create_time"]=>
			        string(10) "1387260660"
			        ["update_time"]=>
			        string(10) "1387263112"
			        ["status"]=>
			        string(1) "1"
			      }
			    }
			    ["page"]=>
			    string(0) ""
			  }
			  ["file"]=>
			  string(48) "./Application/Home/View/default/Index/index.html"
			  ["content"]=>
			  string(0) ""
			  ["prefix"]=>
			  string(0) ""
			}

    		 */
        $engine   =   strtolower(C('TMPL_ENGINE_TYPE')); //'think'
        //$_data['file']>>>'./Application/Home/View/default/Index/index.html'
        $_content  =   empty($_data['content'])?$_data['file']:$_data['content'];
        //C('TMPL_CACHE_PREFIX')>>>''
        //$_data['prefix']>>>''，前缀
        $_data['prefix']   =  !empty($_data['prefix'])?$_data['prefix']:C('TMPL_CACHE_PREFIX');
        if('think'==$engine){ // 采用Think模板引擎
            if((!empty($_data['content']) && $this->checkContentCache($_data['content'],$_data['prefix'])) 
                ||  $this->checkCache($_data['file'],$_data['prefix'])) { // 缓存有效
                //载入模版缓存文件
                Storage::load(C('CACHE_PATH').$_data['prefix'].md5($_content).C('TMPL_CACHFILE_SUFFIX'),$_data['var']);
            }else{
                $tpl = Think::instance('Think\\Template');
                // 编译并加载模板文件
                //$_data['var'］>>> NULL
                //TODO
                $tpl->fetch($_content,$_data['var'],$_data['prefix']);
            }
        }else{
            // 调用第三方模板引擎解析和输出
            if(strpos($engine,'\\')){
                $class  =   $engine;
            }else{
                $class   =  'Think\\Template\\Driver\\'.ucwords($engine);                
            }            
            if(class_exists($class)) {
                $tpl   =  new $class;
                $tpl->fetch($_content,$_data['var']);
            }else {  // 类没有定义
                E(L('_NOT_SUPPERT_').': ' . $class);
            }
        }
    }

    /**
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param string $tmplTemplateFile  模板文件名
     * @return boolean
     */
    protected function checkCache($tmplTemplateFile,$prefix='') {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        $tmplCacheFile = C('CACHE_PATH').$prefix.md5($tmplTemplateFile).C('TMPL_CACHFILE_SUFFIX');
        if(!Storage::has($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > Storage::get($tmplCacheFile,'mtime')) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != 0 && time() > Storage::get($tmplCacheFile,'mtime')+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        // 开启布局模板
        if(C('LAYOUT_ON')) {
            $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').C('TMPL_TEMPLATE_SUFFIX');
            if(filemtime($layoutFile) > Storage::get($tmplCacheFile,'mtime')) {
                return false;
            }
        }
        // 缓存有效
        return true;
    }

    /**
     * 检查缓存内容是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param string $tmplContent  模板内容
     * @return boolean
     */
    protected function checkContentCache($tmplContent,$prefix='') {
        if(Storage::has(C('CACHE_PATH').$prefix.md5($tmplContent).C('TMPL_CACHFILE_SUFFIX'))){
            return true;
        }else{
            return false;
        }
    }    
}
