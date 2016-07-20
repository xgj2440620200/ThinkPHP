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
 * ThinkPHP 引导类
 */
class Think {

    // 类映射
    private static $_map      = array();

    // 实例化对象
    private static $_instance = array();

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function start() {
      // 注册AUTOLOAD方法
      /* spl_autoload_register——注册给定的函数作为__autoload的实现。
       * 将函数注册到SPL __autoload函数队列中。如果该队列中的函数尚未激活，则激活它们。
       * 如果在你的程序中已经实现了__autoload()函数，它必须显示注册到__autoload()函数取代为spl_autoload()或spla_autoload_class()。
       */
      spl_autoload_register('Think\Think::autoload');      
      // 设定错误和异常处理
      /*
       * register_shutdown_function——设置一个当脚本执行完成或意外死掉导致PHP执行即将关闭时可以被调用的函数。
       */
      register_shutdown_function('Think\Think::fatalError');
      //set_error_handler——设置一个用户自定义的错误处理函数
      set_error_handler('Think\Think::appError');
      //set_exception_handler——设置一个用户自定义的异常处理函数
      set_exception_handler('Think\Think::appException');

      // 初始化文件存储方式
      //STORAGE_TYPE——'File'
      Storage::connect(STORAGE_TYPE);	
	
      //APP_MODE——'common'
      //$runtimefile——'./Runtime/common~runtime.php'
      $runtimefile  = RUNTIME_PATH.APP_MODE.'~runtime.php';
      if(!APP_DEBUG && Storage::has($runtimefile)){	//判断是否有这个文件。并没有这个文件。
          Storage::load($runtimefile);	//用include包含并运行此文件。
      }else{
          if(Storage::has($runtimefile))
              Storage::unlink($runtimefile);	//删除文件。
          $content =  '';
          // 读取应用模式
          //CONF_PATH.'core.php'——'./Application/Common/Conf/core.php'
          //MODE_PATH.APP_MODE.'.php'——'D:\WWW\ot\ThinkPHP/Mode/common.php'。包含的是这个文件。
          //包含运行core.php文件，
          //$model实际上就是包含文件中的数组。
          $mode   =   include is_file(CONF_PATH.'core.php')?CONF_PATH.'core.php':MODE_PATH.APP_MODE.'.php';
          //mine>>>
          // 加载核心文件
          foreach ($mode['core'] as $file){
              if(is_file($file)) {
                include $file;
                if(!APP_DEBUG) $content   .= compile($file);	//如果不是调试模式，就编译加载的核心文件。
              }
          }

          // 加载应用模式配置文件
          foreach ($mode['config'] as $key=>$file){
              is_numeric($key)?C(include $file):C($key,include $file);
          }

          // 读取当前应用模式对应的配置文件
          if('common' != APP_MODE && is_file(CONF_PATH.'config_'.APP_MODE.'.php'))
              C(include CONF_PATH.'config_'.APP_MODE.'.php');  

          // 加载模式别名定义
          if(isset($mode['alias'])){
              self::addMap(is_array($mode['alias'])?$mode['alias']:include $mode['alias']);
          }

          // 加载应用别名定义文件
          //CONF_PATH.'alias.php'——'./Application/Common/Conf/alias.php'
          if(is_file(CONF_PATH.'alias.php'))	//没有这个文件。
              self::addMap(include CONF_PATH.'alias.php');

          // 加载模式行为定义。导入了插件
          if(isset($mode['tags'])) {
              Hook::import(is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
          }

          // 加载应用行为定义
          if(is_file(CONF_PATH.'tags.php'))
              // 允许应用增加开发模式配置定义
              Hook::import(include CONF_PATH.'tags.php');   

          // 加载框架底层语言包
          //加载一些语言变量，‘无法加载控制器’之类的。
          L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

          if(!APP_DEBUG){
          	//var_export——输出变的字符串表示，类似于var_dump()。
          	//将插件、语言变量、配置参数、模式别名等吸入到运行时文件中。
              $content  .=  "\nnamespace { Think\Think::addMap(".var_export(self::$_map,true).");";
              $content  .=  "\nL(".var_export(L(),true).");\nC(".var_export(C(),true).');Think\Hook::import('.var_export(Hook::get(),true).');}';
              Storage::put($runtimefile,strip_whitespace('<?php '.$content));	//一个'<?php'连接字符串，就将字符串变成了一个php文件。
          }else{
          	//会加载两个地方的debug.php文件。
            // 调试模式加载系统默认的配置文件
            C(include THINK_PATH.'Conf/debug.php');
            // 读取应用调试配置文件
            if(is_file(CONF_PATH.'debug.php'))
                C(include CONF_PATH.'debug.php');           
          }
      }

      // 读取当前应用状态对应的配置文件
      //APP_STATUS——''
      if(APP_STATUS && is_file(CONF_PATH.APP_STATUS.'.php'))
          C(include CONF_PATH.APP_STATUS.'.php');   

      // 设置系统时区
      //date_default_timezone_set——设定用于一个脚本中所有日期时间函数的默认时区
      //DEFAULT_TIMEZONE——'PRC'
      date_default_timezone_set(C('DEFAULT_TIMEZONE'));

      // 检查应用目录结构 如果不存在则自动创建
      //CHECK_APP_DIR——1
      //LOG_PATH——'./Runtime/Logs/'
      if(C('CHECK_APP_DIR') && !is_dir(LOG_PATH)) {
          // 创建应用目录结构
          //reuqire在包含运行文件时，如果出错会导致脚本终止。
          require THINK_PATH.'Common/build.php';
      }

      // 记录加载文件时间
      G('loadTime');
      // 运行应用
      App::run();//undo
    }

    // 注册classmap
    static public function addMap($class, $map=''){
        if(is_array($class)){
            self::$_map = array_merge(self::$_map, $class);
        }else{
            self::$_map[$class] = $map;
        }        
    }

    /**
     * 类库自动加载
     * 通过类名从映射、系统定义的命名空间和自定义的命名空间中找到类文件，使用include包含并运行。
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
        // 检查是否存在映射
        if(isset(self::$_map[$class])) {
        	//include——包含并且运行指定文件。
            include self::$_map[$class];
        }else{
        	/*
        	 * strstr——查找字符串的首次出现到末尾的字符串
        	 * string strstr(string $haystack, mixed $needle)
        	 * 返回haystack字符串从needle第一次出现的位置开始到haystack结尾的字符串。
        	 * 注意：该函数区分大小写。如果想不区分带瞎写，使用stristr()。
        	 * 如果仅仅想确定needle是否存在于haystack中，使用速度
        	 */
          $name           =   strstr($class, '\\', true);	//将斜线转移
          //is_dir——判断给定的文件名是否是一个目录
          if(in_array($name,array('Think','Org','Behavior','Com','Vendor')) || is_dir(LIB_PATH.$name)){ 
              // Library目录下面的命名空间自动定位
              $path       =   LIB_PATH;
          }else{
              // 检测自定义命名空间 否则就以模块为命名空间
              $namespace  =   C('AUTOLOAD_NAMESPACE');
              $path       =   isset($namespace[$name])? dirname($namespace[$name]).'/' : APP_PATH;
          }
          /* windows的目录'\'和'/'都可以，但是Linux下只能是'/'。
           * 同意目录分隔符
           */
          $filename       =   $path . str_replace('\\', '/', $class) . EXT;
          /*
           * is_file——判断给定文件名是否为一个正常的文件。
           * 参数时文件的路径。
           */
          if(is_file($filename)) {
              // Win环境下面严格区分大小写
              // realpath——返回规范化的绝对路径名
              /*
               * strpos——查找字符串首次出现的位置
               * strpos(string $haystack, mixed $needle[, int $offset])
               * 返回needle在haystack中首次出现的数字位置。
               * 由于位置是从0开始算的，所以要用强制等于false来判断。
               */
              if (IS_WIN && false === strpos(str_replace('/', '\\', realpath($filename)), $class . EXT)){
                  return ;
              }
              include $filename;
          }
        }
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
        	/*
        	 * class_exists——检查类是否已定义
        	 * 参数是类名。
        	 */
            if(class_exists($class)){
                $o = new $class();
                /*
                 * method_exists——检查类的方法是否存在
                 * bool method_exists(mixed $object, string $method)
                 * 检查类的方法是否存在于指定的object中。
                 */
                if(!empty($method) && method_exists($o,$method))
                	/*
                	 * call_user_func——把第一个参数作为回调函数调用
                	 * mixed call_user_func(callable $callback[, mixed $parameter...])
                	 * 第一个参数callback时被调用的回调函数，其余参数时回调函数的参数。
                	 */
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }

    /**
     * 自定义异常处理
     * 将异常放入了保存日志信息的变量中，发送了404的请求头，并输出异常信息。
     * debug>>>因为halt()中并没有做清除内存中日志信息的操作，会不会导致日志信息变量变的很大？
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
        $error = array();
        $error['message']   =   $e->getMessage();	//debug>>>getXXX是自带的方法么？
        $trace              =   $e->getTrace();
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']  =   $e->getFile();
            $error['line']  =   $e->getLine();
        }
        $error['trace']     =   $e->getTraceAsString();
        Log::record($error['message'],Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_PARSE:
          case E_CORE_ERROR:
          case E_COMPILE_ERROR:
          case E_USER_ERROR:
            ob_end_clean();	//debug>>>用这是什么意思？
            $errorStr = "$errstr ".$errfile." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write("[$errno] ".$errorStr,Log::ERR);
            self::halt($errorStr);
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
            self::trace($errorStr,'','NOTIC');
            break;
      }
    }
    
    // 致命错误捕获
    static public function fatalError() {
        Log::save();
        //error_get_last——获取最后发生的错误，是一个关联数组。
        if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:
              case E_PARSE:
              case E_CORE_ERROR:
              case E_COMPILE_ERROR:
              case E_USER_ERROR:  
                ob_end_clean();
                self::halt($e);
                break;
            }
        }
    }

    /**
     * 错误输出
     * 1.判断是否调试模式、cli模式还是普通模式。
     * 2.调试模式是使用debug_backtrace()来获取错误的。
     * 3.正常模式是跳转到ERROR_PAGE，如果没有ERROR_PAGE，就加载TMP_EXCEPTION_FILE，输出错误信息。
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error) {
        $e = array();
        if (APP_DEBUG || IS_CLI) {	//如果是调试模式或者命令行模式。
            //调试模式下输出错误信息
            if (!is_array($error)) {
            	/*	debug_backtrace——产生一条回溯跟踪。
            	 * 	返回一个包含众多关联数组的array。
            	 */
                $trace          = debug_backtrace();
                $e['message']   = $error;
                $e['file']      = $trace[0]['file'];
                $e['line']      = $trace[0]['line'];
                ob_start();	//打开输出控制缓冲
                /*
                 * 	debug_print_backtrace——打印一条回溯
                 * 	打印一条PHP回溯。它打印了函数调用、被included/required的文件和eval()的代码。
                 */
                debug_print_backtrace();
                $e['trace']     = ob_get_clean();//得到当前缓冲区的内容并删除当前输出缓冲。
            } else {
                $e              = $error;
            }
            if(IS_CLI){
            	//PHP_EOL——'\n'
                exit($e['message'].PHP_EOL.'FILE: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']);
            }
        } else {
            //否则定向到错误页面
            $error_page         = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);	//TP自定的URL重定向。
            } else {
                if (C('SHOW_ERROR_MSG'))
                    $e['message'] = is_array($error) ? $error['message'] : $error;
                else
                    $e['message'] = C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        $TMPL_EXCEPTION_FILE=C('TMPL_EXCEPTION_FILE');
        if(!$TMPL_EXCEPTION_FILE){
            //显示在加载配置文件之前的程序错误
            exit('<b>Error:</b>'.$e['message'].' in <b> '.$e['file'].' </b> on line <b>'.$e['line'].'</b>'); 
        }
        include $TMPL_EXCEPTION_FILE;
        exit;
    }

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param boolean $record 是否记录日志
     * @return void
     */
    static public function trace($value='[think]',$label='',$level='DEBUG',$record=false) {
        static $_trace =  array();
        if('[think]' === $value){ // 获取trace信息
            return $_trace;
        }else{
            $info   =   ($label?$label.':':'').print_r($value,true);
            if('ERR' == $level && C('TRACE_EXCEPTION')) {// 抛出异常
                E($info);
            }
            $level  =   strtoupper($level);
            if(!isset($_trace[$level]) || count($_trace[$level])>C('TRACE_MAX_RECORD')) {
                $_trace[$level] =   array();
            }
            $_trace[$level][]   =   $info;
            if((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE')  || $record) {
                Log::record($info,$level,$record);
            }
        }
    }
}
