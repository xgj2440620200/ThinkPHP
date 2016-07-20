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

//----------------------------------
// ThinkPHP公共入口文件
//----------------------------------

/*
 *	1.记录开始运行时间和内存使用
 *	2.定义url模式、系统常量
 *	3.加载核心类文件Think.class.php 
 */

// 记录开始运行时间
/*
 * microtime()——返回当前的unix时间戳和微秒数
 */
$GLOBALS['_beginTime'] = microtime(TRUE);
// 记录内存初始使用
/*
 * function_exists——如果给定的函数已经被定义返回true。
 * 在已经定义的函数列表（包括系统自带的函数和用户自定义的函数）中查找function_name。
 * 注意：对于语法结构的判断，如include_once和echo将会返回false。
 */
/*
 * memory_get_usage——返回分配给PHP的内存变量
 * 返回当前分配给你的PHP脚本的内存变量，单位是字节。
 */
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();

// 版本信息
const THINK_VERSION     =   '3.2.0';

// URL 模式定义
const URL_COMMON        =   0;  //普通模式
const URL_PATHINFO      =   1;  //PATHINFO模式
const URL_REWRITE       =   2;  //REWRITE模式
const URL_COMPAT        =   3;  // 兼容模式

// 类文件后缀
const EXT               =   '.class.php'; 

// 系统常量定义
/*
 * __DIR__,系统常量，指向当前执行的PHP脚本所在的目录。
 * debug——D:\WWW\ot\ThinkPHP/
 */
defined('THINK_PATH') 	or define('THINK_PATH',     __DIR__.'/');
/*
 * $_SERVER['SCRIPT_FILENAME']——当前执行脚本的绝对路径。
 * debug——D:/WWW/ot/index.php
 */
/*
 * dirname——返回路径中的目录部分。
 * 给出一个包含有指向一个文件的全路径的字符串，本函数返回去掉文件名后的目录名。
 */
//dirname($_SERVER['SCRIPT_FILENAME'])——D:/WWW/ot
//APP_PATH——./Application/
defined('APP_PATH') 	or define('APP_PATH',       dirname($_SERVER['SCRIPT_FILENAME']).'/');
//APP_STATUS默认为''。
defined('APP_STATUS')   or define('APP_STATUS',     ''); // 应用状态 加载对应的配置文件
//APP_DEBUG默认关闭。
defined('APP_DEBUG') 	or define('APP_DEBUG',      false); // 是否调试模式

if(function_exists('saeAutoLoader')){// 自动识别SAE环境//不存在这个函数
    defined('APP_MODE')     or define('APP_MODE',      'sae');
    defined('STORAGE_TYPE') or define('STORAGE_TYPE',  'Sae');
}else{
    defined('APP_MODE')     or define('APP_MODE',       'common'); // 应用模式 默认为普通模式    
    defined('STORAGE_TYPE') or define('STORAGE_TYPE',   'File'); // 存储类型 默认为File    
}

defined('RUNTIME_PATH') or define('RUNTIME_PATH',   APP_PATH.'Runtime/');   // 系统运行时目录//D:/WWW/ot/Runtime/
/*
 * realpath——返回规范化的绝对路径名
 * 返回的路径中没有符号链接，'/./'或'/../'成分。
 */
defined('LIB_PATH')     or define('LIB_PATH',       realpath(THINK_PATH.'Library').'/'); // 系统核心类库目录//D:\WWW\ot\ThinkPHP\Library/
defined('CORE_PATH')    or define('CORE_PATH',      LIB_PATH.'Think/'); // Think类库目录//D:\WWW\ot\ThinkPHP\Library/Think/
defined('BEHAVIOR_PATH')or define('BEHAVIOR_PATH',  LIB_PATH.'Behavior/'); // 行为类库目录//D:\WWW\ot\ThinkPHP\Library/Behavior
defined('MODE_PATH')    or define('MODE_PATH',      THINK_PATH.'Mode/'); // 系统应用模式目录//D:\WWW\ot\ThinkPHP/Model/
defined('VENDOR_PATH')  or define('VENDOR_PATH',    LIB_PATH.'Vendor/'); // 第三方类库目录//D:\WWW\ot\ThinkPHP\Library/Vendor/
defined('COMMON_PATH')  or define('COMMON_PATH',    APP_PATH.'Common/'); // 应用公共目录//./Application/Common/
defined('CONF_PATH')    or define('CONF_PATH',      COMMON_PATH.'Conf/'); // 应用配置目录//./Application/Common/Conf/
defined('LANG_PATH')    or define('LANG_PATH',      COMMON_PATH.'Lang/'); // 应用语言目录//./Application/Common/Lang/
defined('HTML_PATH')    or define('HTML_PATH',      APP_PATH.'Html/'); // 应用静态目录//./Application/Html,用于存放生成的静态html
defined('LOG_PATH')     or define('LOG_PATH',       RUNTIME_PATH.'Logs/'); // 应用日志目录//./Runtime/Logs/
defined('TEMP_PATH')    or define('TEMP_PATH',      RUNTIME_PATH.'Temp/'); // 应用缓存目录//./Runtime/Temp/
defined('DATA_PATH')    or define('DATA_PATH',      RUNTIME_PATH.'Data/'); // 应用数据目录//./Runtime/Data/
defined('CACHE_PATH')   or define('CACHE_PATH',     RUNTIME_PATH.'Cache/'); // 应用模板缓存目录//./Runtime/Cache/

// 系统信息
if(version_compare(PHP_VERSION,'5.4.0','<')) {
    ini_set('magic_quotes_runtime',0);//这函数开启就报错，5.3已经放弃。
    define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
}else{
    define('MAGIC_QUOTES_GPC',false);
}
/*
 * substr——返回字符串的子串
 * string substr(string $string, int start[, int $length])
 * 返回字符串string由start和length参数指定的子字符串。
 */
//PHP_SPAI——'cli'。
define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );
//PHP_OS——'Linux'。
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
define('IS_CLI',PHP_SAPI=='cli'? 1   :   0);

if(!IS_CLI) {
    // 当前文件名
    if(!defined('_PHP_FILE_')) {//_PHP_FILE_未定义
        if(IS_CGI) {
            //CGI/FASTCGI模式下
			//$_SERVER['PHP_SELF']——当前执行脚本的文件名，与document root有关。       
            $_temp  = explode('.php',$_SERVER['PHP_SELF']);
            //_PHP_FILE_——'/ot/index.php'
            /*
             * rtrim——删除字符串末端的空白字符（或者其他字符）。
             */
            /*
             * str_replace——子字符串替换
             * str_replace($search, $replace, $subject)
             * 该函数返回一个字符串或者数组。该字符串或数组是将subject中全部的search用replace替换之后的结果。
             * 如果没有一些特殊的替换需求（比如正则表达式），你应该使用该函数替换preg_replace()和preg_repalce()。
             */
            define('_PHP_FILE_',    rtrim(str_replace($_SERVER['HTTP_HOST'],'',$_temp[0].'.php'),'/'));
        }else {
            define('_PHP_FILE_',    rtrim($_SERVER['SCRIPT_NAME'],'/'));
        }
    }
    if(!defined('__ROOT__')) {	//__ROOT__没有定义
    	//$_root——'//'/ot'
        $_root  =   rtrim(dirname(_PHP_FILE_),'/');	
        //__ROOT__——'/ot'
        define('__ROOT__',  (($_root=='/' || $_root=='\\')?'':$_root));	
    }
}

// 加载核心Think类
//CORE_PATH.'Think'.EXT——'D:\WWW\ot\ThinkPHP\Library/Think/Think.class.php'。就是Think.class.php的文件位置。
require CORE_PATH.'Think'.EXT;
// 应用初始化 
Think\Think::start();