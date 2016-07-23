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
namespace Common\Behavior;
use Think\Behavior;
use Think\Hook;
defined('THINK_PATH') or exit();

// 初始化钩子信息
class InitHookBehavior extends Behavior {

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        if(isset($_GET['m']) && $_GET['m'] === 'Install') return;
        
        //$data是由标签位置和对应的行为类命名空间组成。
        $data = S('hooks');
        /*
         * $data>>>
         * array(13) {
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
			  ["app_init"]=>
			  array(1) {
			    [0]=>
			    string(24) "Common\Behavior\InitHook"
			  }
			  ["documentEditForm"]=>
			  array(1) {
			    [0]=>
			    string(10) "Attachment"
			  }
			  ["documentDetailAfter"]=>
			  array(2) {
			    [0]=>
			    string(10) "Attachment"
			    [1]=>
			    string(13) "SocialComment"
			  }
			  ["documentSaveComplete"]=>
			  array(1) {
			    [0]=>
			    string(10) "Attachment"
			  }
			  ["documentEditFormContent"]=>
			  array(1) {
			    [0]=>
			    string(6) "Editor"
			  }
			  ["adminArticleEdit"]=>
			  array(1) {
			    [0]=>
			    string(14) "EditorForAdmin"
			  }
			  ["AdminIndex"]=>
			  array(3) {
			    [0]=>
			    string(8) "SiteStat"
			    [1]=>
			    string(10) "SystemInfo"
			    [2]=>
			    string(7) "DevTeam"
			  }
			  ["topicComment"]=>
			  array(1) {
			    [0]=>
			    string(6) "Editor"
			  }
			}

         */
        if(!$data){
            $hooks = M('Hooks')->getField('name,addons');
            foreach ($hooks as $key => $value) {
                if($value){
                    $map['status']  =   1;
                    $names          =   explode(',',$value);
                    $map['name']    =   array('IN',$names);
                    $data = M('Addons')->where($map)->getField('id,name');
                    if($data){
                        $addons = array_intersect($names, $data);
                        Hook::add($key,$addons);
                    }
                }
            }
            S('hooks',Hook::get());
        }else{
            Hook::import($data,false);
        }
    }
}