<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/*
 * 主要工作：
 * 1.比较PHP版本
 * 2.定义几个常量，APP_DEBUG、APP_PATH、RUNTIME_PATH
 * 3.引入框架核心文件ThinkPHP.php
 */
/*
 * version_compare——对比两个PHP规范化的版本数字字符串
 * mixed version_compare(string $version1, string $version2[, string $operator])
 * 默认情况下，在第一个版本低于第二个版本时，version_compare()返回-1；如果两者相等，返回0；第二个版本更低时则发挥1.
 * 当使用了可选参数operator时，如果关系是操作符所指定的那个，函数将返回true，否则返回false。
 */
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

/**
 * 系统调试设置
 * 项目正式部署后请设置为false
 */
define ( 'APP_DEBUG', true );	//将系统的设置为调试模式。

/**
 * 应用目录设置
 * 安全期间，建议安装调试完成后移动到非WEB目录
 */
/*
 * 输出'./Application/'。
 */
define ( 'APP_PATH', './Application/' );
/*
 * is_file——判断给定文件名是否为一个正常的文件
 */
if(!is_file(APP_PATH . 'User/Conf/config.php')){
	header('Location: ./install.php');//这是在安装完ot之前用的。现在install.php文件已经删除了。
	exit;
}

/**
 * 缓存目录设置
 * 此目录必须可写，建议移动到非WEB目录
 */
define ( 'RUNTIME_PATH', './Runtime/' );//注意，如果此目录在Linux等服务器上，一定要让此目录可写。

/**
 * 引入核心入口
 * ThinkPHP亦可移动到WEB以外的目录
 */
require './ThinkPHP/ThinkPHP.php';