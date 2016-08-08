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
 * ThinkPHP Model模型类
 * 实现了ORM和ActiveRecords模式
 */
class Model {
    // 操作状态
    const MODEL_INSERT          =   1;      //  插入模型数据
    const MODEL_UPDATE          =   2;      //  更新模型数据
    const MODEL_BOTH            =   3;      //  包含上面两种方式
    const MUST_VALIDATE         =   1;      // 必须验证
    const EXISTS_VALIDATE       =   0;      // 表单存在字段则验证
    const VALUE_VALIDATE        =   2;      // 表单值不为空则验证
	
    //通过设置属性为的访问权限为protected来实现单例功能
    // 当前数据库操作对象
    protected $db               =   null;
    // 主键名称
    protected $pk               =   'id'; //默认了主键名称是'id'
    // 主键是否自动增长
    protected $autoinc          =   false;    
    // 数据表前缀
    protected $tablePrefix      =   null;
    // 模型名称
    protected $name             =   '';
    // 数据库名称
    protected $dbName           =   '';
    //数据库配置
    protected $connection       =   '';
    // 数据表名（不包含表前缀）
    protected $tableName        =   '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName    =   '';
    // 最近错误信息
    protected $error            =   '';
    // 字段信息
    protected $fields           =   array();
    // 数据信息
    protected $data             =   array();
    // 查询表达式参数
    protected $options          =   array();  //是一个二维数组，键名是sql关键字
    protected $_validate        =   array();  // 自动验证定义
    protected $_auto            =   array();  // 自动完成定义
    protected $_map             =   array();  // 字段映射定义
    protected $_scope           =   array();  // 命名范围定义
    // 是否自动检测数据表字段信息
    protected $autoCheckFields  =   true;
    // 是否批处理验证
    protected $patchValidate    =   false;
    // 链操作方法列表
    protected $methods          =   array('order','alias','having','group','lock','distinct','auto','filter','validate','result','token');

    /**
     * 架构函数
     * 取得DB类的实例对象 字段检查
     * 1.模型初始化（为空，需要子类具体实现）
     * 2.$name或者getModelName()获取模型名称
     * 3.通过$tablePrefxi或者C('DB_Prefix')设置表前缀
     * 4.通过$connection初始化数据库连接
     * @access public
     * @param string $name 模型名称
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     */
    public function __construct($name='',$tablePrefix='',$connection='') {  //$tablePrefix为''好像没有用，因为根本就没有用到''。
        // 模型初始化
        $this->_initialize();
        // 获取模型名称
        if(!empty($name)) {
            if(strpos($name,'.')) { // 支持 数据库名.模型名的 定义
                list($this->dbName,$this->name) = explode('.',$name);
            }else{
                $this->name   =  $name;  //name是模型名称
            }
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();  //debug>>没有实测
        }
        //$this->name>>>'Config'
        // 设置表前缀
        //C('DB_PREFIX')>>>'onethink_'
        if(is_null($tablePrefix)) {// 前缀为Null表示没有前缀
            $this->tablePrefix = '';
        }elseif('' != $tablePrefix) {
            $this->tablePrefix = $tablePrefix;
        }elseif(!isset($this->tablePrefix)){
            $this->tablePrefix = C('DB_PREFIX');
        }

        // 数据库初始化操作
        // 获取数据库操作对象
        // 当前模型有独立的数据库连接信息
        // 获取了字段信息
        $this->db(0,empty($this->connection)?$connection:$this->connection,true);  //单例功能
    }

    /**
     * 自动检测数据表信息
     * 调用了flush()方法
     * 获取字段信息，并缓存
     * @access protected
     * @return void
     */
    protected function _checkTableInfo() {
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录
        if(empty($this->fields)) {
            // 如果数据表字段没有定义则自动获取
            if(C('DB_FIELDS_CACHE')) { //启用字段缓存
                $db   =  $this->dbName?$this->dbName:C('DB_NAME');
                $fields = F('_fields/'.strtolower($db.'.'.$this->name));
                if($fields) {
                    $this->fields   =   $fields;
                    $this->pk       =   $fields['_pk'];
                    return ;
                }
            }
            // 每次都会读取数据表信息
            $this->flush();
        }
    }

    /**
     * 获取字段信息并缓存
     * 缓存是赋值给属性，如果配置了'DB_FIELDS_CACHE'，还要将字段信息写入文件。
     * @access public
     * @return void
     */
    public function flush() {
        // 缓存不存在则查询数据表信息
        //$this->db是mysqli的数据库连接对象
        $this->db->setModel($this->name); //mysqli并没有这个方法
        $fields =   $this->db->getFields($this->getTableName()); //一个字段信息组成的二维关联数组
        if(!$fields) { // 无法获取字段信息
            return false;
        }
        $this->fields   =   array_keys($fields);  //获取字段名，并不需要使用字段对应的各种属性。
        foreach ($fields as $key=>$val){
            // 记录字段类型
            $type[$key]     =   $val['type'];
            if($val['primary']) {
                $this->pk   =   $key;  //注意：这里用表字段信息中的主键列名赋值给模型类的pk属性。虽然pk默认是'id'，但是可以变。即如果想使用pk相关的方法，不必数据表设计限制为'id'。	
                $this->fields['_pk']   =   $key;  //fileds属性中多了'_pk'，方便其他方法的操作。
                if($val['autoinc']) $this->autoinc   =   true;
            }
        }
        // 记录字段类型信息，$this->fields['_tyep']是一个字段类型组成的数组
        $this->fields['_type'] =  $type; //$type是个数组

        // 2008-3-7 增加缓存开关控制
        if(C('DB_FIELDS_CACHE')){
            // 永久缓存数据表信息
            $db   =  $this->dbName?$this->dbName:C('DB_NAME');
            F('_fields/'.strtolower($db.'.'.$this->name),$this->fields);	//第一个参数是路径
        }
    }

    //下面的__set()、__get()、__unset()、__isset()，用于封装属性的操作，不让外部与类耦合
    /**
     * 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name,$value) {
        // 设置数据对象属性
        $this->data[$name]  =   $value;
    }

    /**
     * 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name) {
        return isset($this->data[$name])?$this->data[$name]:null;
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * 包括：在methods中定义的连贯操作，count、sum、min、max、avg统计查询，getBy，getFieldBy，scope
     * 注意：其中有几个连贯操作在文档中没有全部找到,如token。或者说methods中定义的方法名，是用来合成options的简单键值对，因为并没有对其中的方法进行解析。
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method,$args) {
    	//$this->methods是由连贯操作标识符组成的一位数组，array('order','alia')
    	//in_array()中第三个参数是true时，还会检查needle的类型是否和haystack中的相同
        if(in_array(strtolower($method),$this->methods,true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            // 统计查询的实现。统计查询实际上是某个字段，count('name') name。
            $field =  isset($args[0])?$args[0]:'*';	//是否指定了字段，默认是'*'
            //getField()获取一条记录的某个值
            return $this->getField(strtoupper($method).'('.$field.') AS tp_'.$method);
            /*
             * getByXXX()，根据某个字段的值去找某条记录。
             * 实际上由where()和find()实现。
             */
        }elseif(strtolower(substr($method,0,5))=='getby') {	//用的是substr()截取'getby'的， 后面的是字段名
            // 根据某个字段获取记录
            //parse_name()只是端标识符命名规范进行转换
            $field   =   parse_name(substr($method,5));	//获取字段名
            $where[$field] =  $args[0];
            return $this->where($where)->find();
            /*
             * getFieldByXXX('a', 'b')
             * 实际上由where()和getField()实现
             */
        }elseif(strtolower(substr($method,0,10))=='getfieldby') {
            // 根据某个字段获取记录的某个值
            $name   =   parse_name(substr($method,10));
            $where[$name] =$args[0];	//第一个参数是where()的值
            return $this->where($where)->getField($args[1]);	//第二个参数是getField()的参数
        }elseif(isset($this->_scope[$method])){// 命名范围的单独调用支持，要在自定义模型中给该属性赋值。必须要用D()定义模型
        	/*
        	 * 在自定义模型中:
        	 * protected $_scope = array(
        	 * 		'normal' => array(
        	 * 			'where' => array('staus' => 1)
        	 * 		)
        	 * )
        	 */
        	//设置属性值，是一个操作
            return $this->scope($method,$args[0]);	//这是一个方法
        }else{
        	//L('_METHOD_NOT_EXIST_')>>>'方法不存在！'。调用的方法tp没有定义
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }
    // 回调方法 初始化模型
    protected function _initialize() {}  //这个_initialize()可以在子类中实现。

    /**
     * 对保存到数据库的数据进行处理
     * 1.获取待检查的字段数获取表的全部字段
     * 2.对每个字段进行是否存在的判断和销毁，字段类型检测和强制转换
     * 3.如果设置了optinos['filer']，就通过options['filter']和array_map()进行安全过滤
     * 4.返回检查后的数据
     * @access protected
     * @param mixed $data 要操作的数据
     * @return boolean
     */
     protected function _facade($data) {

        // 检查数据字段合法性
        if(!empty($this->fields)) {
            if(!empty($this->options['field'])) {
                $fields =   $this->options['field'];
                unset($this->options['field']);	//主动释放了属性options的内存
                if(is_string($fields)) {	//将字符串形式转化成数组形式
                    $fields =   explode(',',$fields);
                }    
            }else{	//没有使用filed()，就默认检查所有字段
                $fields =   $this->fields;
            }        
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields,true)){	//对于不存在数据表中的字段，进行unset()。所以，在用add()时，不必担心添加了数据表字段之外或者错误字段
                    unset($data[$key]);
                    /*
                     * is_scalar——检测变量是否是一个标量
                     * 标量变量包括integer、float、string或boolean的变量，而array、object和resource则不是标量
                     */
                }elseif(is_scalar($val)) {
                    // 字段类型检查 和 强制转换。一般数据表中都是整形、浮点型、字符串，没有其他结构。对于数组、对象等结构，序列化后，可以存放到数据表中
                    $this->_parseType($data,$key);
                }
            }
        }
       
        // 安全过滤，使用回调函数对$data进行处理，并销毁$this->optinos['filter']
        if(!empty($this->options['filter'])) {	//debug>>>哪里使用的
            $data = array_map($this->options['filter'],$data);
            unset($this->options['filter']);
        }
        $this->_before_write($data);	//需要在自定义模型中实现
        return $data;
     }

    // 写入数据前的回调方法 包括新增和更新
    protected function _before_write(&$data) {}

    /**
     * 新增数据
     * 1.通过判断$data是否为空，来决定是否使用对象。如果对象的data属性不为空，赋值给$data。否则，报“非法数据对象！”的错误
     * 2.对$options使用_parseOptions()进行解析，一般没有第二个参数
     * 3.根据数据表的字段类型，调用_facade()对$data中的字段进行类型检测和强制转换
     * 4.尝试调用_before_insert()
     * 5.调用db类的insert()将数据写入到数据库
     * 6.返回最后写入的主键编号或者写入的数据的数量
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function add($data='',$options=array(),$replace=false) {
        if(empty($data)) {	//通过判断是否专递$data参数来分辨是否使用对象
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {	//data属性用来存放数据
                $data           =   $this->data;
                // 重置数据
                $this->data     = array();	//在取值后就将数组中的数据释放了，算是个优化，自己不必在控制器中手动清除对象中使用过的数据
            }else{
            	//L('_DATA_TYPE_INVALID_')>>>"非法数据对象！"
            	//给error属性赋值
                $this->error    = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 分析表达式
        //虽然$options为空数组，但是在_parseOptinos()中用array_merge()合并了options属性和$options
        $options    =   $this->_parseOptions($options);	//一般不再add()中添加表达式
        // 数据处理
        //根据数据表的字段类型，对添加的数据进行类型检测，并强制转换其类型
        $data       =   $this->_facade($data);
        //_before_insert()方法为空，需要在自定义模型中实现
        if(false === $this->_before_insert($data,$options)) {
            return false;
        }
        // 写入数据到数据库
        //$this->db>>>'Think\Db\Driver\Mysqli'
        $result = $this->db->insert($data,$options,$replace);	//返回的是记录数
        //自增主键返回插入ID
        if(false !== $result ) {
        	//获取最后插入记录的编号
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                // 自增主键返回插入ID
                $data[$this->getPk()]  = $insertId;
                $this->_after_insert($data,$options);	//需要在自定义模型中实现
                return $insertId;
            }
            $this->_after_insert($data,$options);	//要在自定义模型中实现
        }
        //非自增主键返回结果集数量
        return $result;
    }
    // 插入数据前的回调方法
    /**
     * 在自定模型中实现，对数据写入数据库前做最后一步操作 
     */
    protected function _before_insert(&$data,$options) {}
    // 插入成功后的回调方法
    /**
     * 插入成功后执行的回调方法 
     */
    protected function _after_insert($data,$options) {}

    /**
     * 批量添加
     * 1.判断$datalist是否为空，空则包“无效数据类型”错误
     * 2.分析$optinos表达式，合并options属性
     * 3.对数据的字段、类型进行检测，如果有options['filter']，就进行回调安全过滤
     * 4.调用mysqli类中的insertAll()进行批量添加
     * 5.返回最后插入的记录编号或者插入的记录数
     * @params array $datalist  必须二维数组，否则直接返回false
     * @params array $options	  参数表达式，可以为空
     * @params bool $replace 相同的记录是否替换
     * @return int 最后插入记录的编号或者影响的记录数 
     */
    public function addAll($dataList,$options=array(),$replace=false){
        if(empty($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');	//无效的数据类型
            return false;
        }
        // 分析表达式，合并options属性
        $options =  $this->_parseOptions($options);
        // 数据处理
        foreach ($dataList as $key=>$data){
        	//对数据字段、类型进行检测，如果有optinos['filter']，通过回调进行安全过滤
            $dataList[$key] = $this->_facade($data);
        }
        // 写入数据到数据库
        $result = $this->db->insertAll($dataList,$options,$replace);
        //返回最后插入的记录编号或者插入的记录数
        if(false !== $result ) {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                return $insertId;
            }
        }
        return $result;
    }

    /**
     * 通过Select方式添加记录
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @param array $options 表达式
     * @return boolean
     */
    public function selectAdd($fields='',$table='',$options=array()) {
        // 分析表达式
        $options =  $this->_parseOptions($options);
        // 写入数据到数据库
        if(false === $result = $this->db->selectInsert($fields?$fields:$options['field'],$table?$table:$this->getTableName(),$options)){
            // 数据库插入操作失败
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            // 插入成功
            return $result;
        }
    }

    /**
     * 保存数据
     * 1.判断$data是否为空，决定使用对象中的数据还是报错。如果data不为空，将值赋给$data;否则，报'非法数据对象!'错误
     * 2.对数据进行字段名、字段类型的检测和强制转换，表达式过滤
     * 3.分析表达式，一般没有$options参数
     * 4.获取主键名称
     * 5.调用更新前回调方法
     * 6.调用mysqli的excute()
     * 7.调用更新后的回调方法
     * 8.返回结果
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save($data='',$options=array()) {
        if(empty($data)) {	//如果用对象，要多一步解析操作
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data           =   $this->data;
                // 重置数据
                $this->data     =   array();
            }else{
            	//L('_DATA_TYPE_INVALID_')——'非法数据对象!'
                $this->error    =   L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 数据处理
        $data       =   $this->_facade($data);
        // 分析表达式
        $options    =   $this->_parseOptions($options);
        $pk         =   $this->getPk();
        if(!isset($options['where']) ) {
            // 如果存在主键数据 则自动作为更新条件
            if(isset($data[$pk])) {	//如果数据集中有主键的字段名，可以不需调用where(),通过获取data中的主键值，来新增一个where元素
                $where[$pk]         =   $data[$pk];
                $options['where']   =   $where;
                unset($data[$pk]);	//释放内存
            }else{
                // 如果没有任何更新条件则不执行
                //L('_OPERATION_WRONG_')>>>'操作出现错误'
                $this->error        =   L('_OPERATION_WRONG_');
                return false;
            }
        }
        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue    =   $options['where'][$pk];	//获取主键值
        }        
        if(false === $this->_before_update($data,$options)) {	//自定义模型中实现
            return false;
        }        
        //按sql的update标准形式进行解析、拼接sql语句，并调用mysqli的excute()来执行
        $result     =   $this->db->update($data,$options);
        if(false !== $result) {
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_update($data,$options);
        }
        return $result;
    }
    
    // 更新数据前的回调方法
    /**
     *	更新数据前的回调方法，在自定义模型中定义
     */
    protected function _before_update(&$data,$options) {}
    
    // 更新成功后的回调方法
    /**
     * 更新成功后的回调方法，在自定义模型中定义 
     */
    protected function _after_update($data,$options) {}

    /**
     * 删除数据
     * 1.判断参数是否为空，空，则尝试获取数据对象中的主键值，递归调用delete()
     * 2.支持整数、字符串的参数，如果不是多个主键，用整数作为参数，将主键值赋给$optinos['where']
     * 3.调用_parseOptions()，主要是检查数据表是否有主键
     * 4.调用删除前回调方法，mysqli的delete()，删除后回调方法
     * 5.返回删除个数
     * @access public
     * @param mixed $options 表达式
     * @return mixed
     */
    public function delete($options=array()) {
        if(empty($options) && empty($this->options['where'])) {	
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if(!empty($this->data) && isset($this->data[$this->getPk()]))	//data数据对象的主键原来都可以用于删除
                return $this->delete($this->data[$this->getPk()]);	//递归调用delte()，传入主键值
            else
                return false;
        }
        //没有传入参数，data数据对象中也没有主键
        $pk   =  $this->getPk();
        if(is_numeric($options)  || is_string($options)) {	//如果不是多个主键，最好用整形。如果是字符串，sql执行时会有自动转型
            // 根据主键删除记录
            if(strpos($options,',')) {	//支持传入用逗号分割主键的字符串。并没有解析，直接使用'IN'关键字
                $where[$pk]     =  array('IN', $options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }
        // 分析表达式
        $options =  $this->_parseOptions($options);	//如果表并没有主键，那么后面的判断是必要的。
        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue            =  $options['where'][$pk];
        }
        if(false === $this->_before_delete($options)) {
            return false;
        }        
        $result  =    $this->db->delete($options);
        if(false !== $result) {
            $data = array();
            if(isset($pkValue)) $data[$pk]   =  $pkValue;	//debug>>>数据已经删除了，这个数据对象的主键获取还有作用么？
            $this->_after_delete($data,$options);
        }
        // 返回删除记录个数
        return $result;
    }
    // 删除数据前的回调方法
    /**
     * 删除数据前的回调方法，需要在自定义模型中定义
     */
    protected function _before_delete($options) {}    
    // 删除成功后的回调方法
    /**
     * 删除数据后的回调方法，需要在自定义模型中定义 
     */
    protected function _after_delete($data,$options) {}

    /**
     * 查询数据集
     * 1.判断参数是否为数字或者数字形式的字符串，是则，用参数生成一个$optinos['where']
     * 2._parseOpionts()分析表达式
     * 3.判断是否设置了查询缓存cache()，是则尝试取值
     * 4.调用db的select()
     * 5.对查询结果集进行字段检测
     * 6.设置了cache()，就进行缓存。否则直接跳到下步
     * 7.返回查询结果集
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
    public function select($options=array()) {	//debug>>>select()是否支持result()连贯操作？
    	//如果$options是字符串或者数字，尝试根据主键查询
        if(is_string($options) || is_numeric($options)) {
            // 根据主键查询
            $pk   =  $this->getPk();
            if(strpos($options,',')) {
                $where[$pk]     =  array('IN',$options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }elseif(false === $options){ // 用于子查询 不查询只返回SQL
            $options            =  array();
            // 分析表达式
            $options            =  $this->_parseOptions($options);
            return  '( '.$this->db->buildSelectSql($options).' )';	//select(false)生成子查询，内部用到的还是buildSelectSql()
        }
        // 分析表达式
        $options    =  $this->_parseOptions($options);
        // 判断查询缓存debug>>>没有用过
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5(serialize($options));
            $data   =   S($key,'',$cache);	//S($key, '')是用来取数据的，S()中第二个参数默认是''，在''的情况下，是取数据的
            if(false !== $data){
                return $data;
            }
        }    
        $resultSet  = $this->db->select($options);
        if(false === $resultSet) {	//sql语句出现错误
            return false;
        }
        if(empty($resultSet)) { // 查询结果为空
            return null;
        }
        //对查询结果集进行字段检测，销毁不在字段映射中对应的值
        $resultSet  =   array_map(array($this,'_read_data'),$resultSet);
        $this->_after_select($resultSet,$options);
        if(isset($cache)){
            S($key,$resultSet,$cache);	//如果设置了cache，这里将缓存查询结果集
        }           
        return $resultSet;
    }
    
    // 查询成功后的回调方法
    /**
     * 查询成功后的回调方法，在自定模型中定义 
     */
    protected function _after_select(&$resultSet,$options) {}

    /**
     * 生成查询SQL 可用于子查询
     * @access public
     * @param array $options 表达式参数
     * @return string
     */
    public function buildSql($options=array()) {
        // 分析表达式
        $options =  $this->_parseOptions($options);
        return  '( '.$this->db->buildSelectSql($options).' )';
    }

    /**
     * 分析表达式
     * 1.获取表字段信息，包括字段名、类型等
     * 2.清空$this->optinos，避免影响下次查询
     * 3.获取数据表名、模型名称
     * 4.通过foreach()和_parseType()进行类型检测和强制转换
     * 5.表达式过滤
     * 6.返回解析后的$options
     * @access protected
     * @param array $options 表达式参数
     * @return array
     */
    protected function _parseOptions($options=array()) {
        if(is_array($options)) //debug>>>这么早合并是为什么？
            $options =  array_merge($this->options,$options);

        if(!isset($options['table'])){
            // 自动获取表名
            $options['table']   =   $this->getTableName();
            $fields             =   $this->fields;
        }else{
            // 指定数据表 则重新获取字段列表 但不支持类型检测
            $fields             =   $this->getDbFields();
        }
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options  =   array();	//debug>>>如果是直接清空，为什么开始还有合并一次options属性？
        // 数据表别名
        if(!empty($options['alias'])) {
            $options['table']  .=   ' '.$options['alias'];
        }
        // 记录操作的模型名称
        $options['model']       =   $this->name;

        // 字段类型验证
        if(isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            // 对数组查询条件进行字段类型检查
            foreach ($options['where'] as $key=>$val){
                $key            =   trim($key);
                if(in_array($key,$fields,true)){
                    if(is_scalar($val)) {
                        $this->_parseType($options['where'],$key);
                    }
                }elseif(!is_numeric($key) && '_' != substr($key,0,1) && false === strpos($key,'.') && false === strpos($key,'(') && false === strpos($key,'|') && false === strpos($key,'&')){
                    unset($options['where'][$key]);
                }
            }
        }

        // 表达式过滤
        $this->_options_filter($options);
        return $options;
    }
    
    // 表达式过滤回调方法
    /**
     * 表达式过滤回调方法 
     */
    protected function _options_filter(&$options) {}

    /**
     * 数据类型检测，并进行转换。在检测的字段类型中，一定进行类型转换操作。
     * 即使在填充数据的时候写错了数据类型，tp也会根据数据表的对应字段类型，进行转换，用类似intval()、floatval()、(bool)的函数
     * 检测顺序是:'enum'=>'int'=>'double'=>'boo'，并没有检测字符串类型
     * @access protected
     * @param mixed $data 数据
     * @param string $key 字段名
     * @return void
     */
    protected function _parseType(&$data,$key) { //这里$data是引用传值
    	//debug>>>bind这个参数是在哪里绑定的？
        if(empty($this->options['bind'][':'.$key]) && isset($this->fields['_type'][$key])){ //后面一个条件是表的字段类型属性必须设置
            $fieldType = strtolower($this->fields['_type'][$key]);	//debug>>>把数据表字段类型转换成小写，有必要么？本来不都是小写么
            if(false !== strpos($fieldType,'enum')){
                // 支持ENUM类型优先检测。
            }elseif(false === strpos($fieldType,'bigint') && false !== strpos($fieldType,'int')) {
                $data[$key]   =  intval($data[$key]);
            }elseif(false !== strpos($fieldType,'float') || false !== strpos($fieldType,'double')){
                $data[$key]   =  floatval($data[$key]);
            }elseif(false !== strpos($fieldType,'bool')){
                $data[$key]   =  (bool)$data[$key];
            }
        }
    }

    /**
     * 数据读取后的处理
     * 销毁查询结果集中对应字段没有在_map中的值
     * debug>>>有什么作用？字段检测不是在执行sql前就执行过吗？
     * @access protected
     * @param array $data 当前数据
     * @return array
     */
    protected function _read_data($data) {
        // 检查字段映射
        //C('READ_DATA_MAP')>>>NULL
        //debug>>>_map属性是在哪里赋值的？
        if(!empty($this->_map) && C('READ_DATA_MAP')) {
            foreach ($this->_map as $key=>$val){
                if(isset($data[$val])) {
                    $data[$key] =   $data[$val];
                    unset($data[$val]);
                }
            }
        }
        return $data;
    }

    /**
     * 查询数据
     * find()实际上是加了limit 1 的select()
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
    public function find($options=array()) {
        if(is_numeric($options) || is_string($options)) {	//根据主键查询，只能传入一个主键，否则会把整个字符串当作主键，导致查询错误
            $where[$this->getPk()]  =   $options;	//这里是直接赋值的，并没有用判断是否有','，直接使用TP的array('IN', $options)
            $options                =   array();
            $options['where']       =   $where;
        }
        // 总是查找一条记录
        $options['limit']   =   1;	//自动给find()的limit赋值1，所以不用再find()中显示加limit 1，来优化查询
        // 分析表达式
        $options            =   $this->_parseOptions($options);
        // 判断查询缓存
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5(serialize($options));
            $data   =   S($key,'',$cache);
            if(false !== $data){
                $this->data     =   $data;
                return $data;
            }
        }
        $resultSet          =   $this->db->select($options);
        if(false === $resultSet) {
            return false;
        }
        if(empty($resultSet)) {// 查询结果为空
            return null;
        }
        // 读取数据后的处理
        $data   =   $this->_read_data($resultSet[0]);	//在select()中，用的是array_map()。find()只有一个结果，就不用调用array_map()了。
        $this->_after_find($data,$options);
        if(!empty($this->options['result'])) {	//用于返回数据转换。包括自定义方法回调或者转换成json、xml格式。
            return $this->returnResult($data,$this->options['result']);
        }
        $this->data     =   $data;
        if(isset($cache)){
            S($key,$data,$cache);
        }
        return $this->data;
    }
    
    // 查询成功的回调方法
    /**
     * 查询成功后的回调方法 
     */
    protected function _after_find(&$result,$options) {}

    /**
     *	用于返回数据转换
     *	用$type作为回调方法处理$data，或者直接转换成json、xml格式的数据 
     */
    protected function returnResult($data,$type=''){
        if ($type){
            if(is_callable($type)){
                return call_user_func($type,$data);
            }
            switch (strtolower($type)){
                case 'json':
                    return json_encode($data);
                case 'xml':
                    return xml_encode($data);
            }
        }
        return $data;
    }

    /**
     * 处理字段映射
     * 读取时对结果集中的字段进行过滤，销毁单元
     * debug>>>写？
     * @access public
     * @param array $data 当前数据
     * @param integer $type 类型 0 写入 1 读取
     * @return array
     */
    public function parseFieldsMap($data,$type=1) {
        // 检查字段映射
        if(!empty($this->_map)) {
            foreach ($this->_map as $key=>$val){
                if($type==1) { // 读取
                    if(isset($data[$val])) {
                        $data[$key] =   $data[$val];
                        unset($data[$val]);
                    }
                }else{
                    if(isset($data[$key])) {
                        $data[$val] =   $data[$key];
                        unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 设置记录的某个字段值
     * 参数支持数关联组和字符串形式，赋值给数据对象，调用save()
     * 支持使用数据库字段和方法
     * @access public
     * @param string|array $field  字段名
     * @param string $value  字段值
     * @return boolean
     */
    public function setField($field,$value='') {
    	//给数据对象赋值
        if(is_array($field)) {
            $data           =   $field;
        }else{
            $data[$field]   =   $value;
        }
        //调用save()
        return $this->save($data);
    }

    /**
     * 字段值增长
     * @access public
     * @param string $field  字段名
     * @param integer $step  增长值
     * @return boolean
     */
    public function setInc($field,$step=1) {
        return $this->setField($field,array('exp',$field.'+'.$step));
    }

    /**
     * 字段值减少
     * @access public
     * @param string $field  字段名
     * @param integer $step  减少值
     * @return boolean
     */
    public function setDec($field,$step=1) {
        return $this->setField($field,array('exp',$field.'-'.$step));
    }

    /**
     * 获取一条记录的某个字段值
     * @access public
     * @param string $field  字段名
     * @param string $spea  字段数据间隔符号 NULL返回数组
     * @return mixed
     */
    public function getField($field,$sepa=null) {
        $options['field']       =   $field;
        $options                =   $this->_parseOptions($options);
        // 判断查询缓存
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5($sepa.serialize($options));
            $data   =   S($key,'',$cache);
            if(false !== $data){
                return $data;
            }
        }        
        $field                  =   trim($field);
        if(strpos($field,',')) { // 多字段
            if(!isset($options['limit'])){
                $options['limit']   =   is_numeric($sepa)?$sepa:'';
            }
            $resultSet          =   $this->db->select($options);
            if(!empty($resultSet)) {
                $_field         =   explode(',', $field);
                $field          =   array_keys($resultSet[0]);
                $key            =   array_shift($field);
                $key2           =   array_shift($field);
                $cols           =   array();
                $count          =   count($_field);
                foreach ($resultSet as $result){
                    $name   =  $result[$key];
                    if(2==$count) {
                        $cols[$name]   =  $result[$key2];
                    }else{
                        $cols[$name]   =  is_string($sepa)?implode($sepa,$result):$result;
                    }
                }
                if(isset($cache)){
                    S($key,$cols,$cache);
                }
                return $cols;
            }
        }else{   // 查找一条记录
            // 返回数据个数
            if(true !== $sepa) {// 当sepa指定为true的时候 返回所有数据
                $options['limit']   =   is_numeric($sepa)?$sepa:1;
            }
            $result = $this->db->select($options);
            if(!empty($result)) {
                if(true !== $sepa && 1==$options['limit']) {
                    $data   =   reset($result[0]);
                    if(isset($cache)){
                        S($key,$data,$cache);
                    }            
                    return $data;
                }
                foreach ($result as $val){
                    $array[]    =   $val[$field];
                }
                if(isset($cache)){
                    S($key,$array,$cache);
                }                
                return $array;
            }
        }
        return null;
    }

    /**
     * 创建数据对象 但不保存到数据库
     * @access public
     * @param mixed $data 创建数据
     * @param string $type 状态
     * @return mixed
     */
     public function create($data='',$type='') {
        // 如果没有传值默认取POST数据
        if(empty($data)) {
            $data   =   I('post.');
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        // 验证数据
        if(empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        // 状态
        $type = $type?$type:(!empty($data[$this->getPk()])?self::MODEL_UPDATE:self::MODEL_INSERT);

        // 检查字段映射
        if(!empty($this->_map)) {
            foreach ($this->_map as $key=>$val){
                if(isset($data[$key])) {
                    $data[$val] =   $data[$key];
                    unset($data[$key]);
                }
            }
        }

        // 检测提交字段的合法性
        if(isset($this->options['field'])) { // $this->field('field1,field2...')->create()
            $fields =   $this->options['field'];
            unset($this->options['field']);
        }elseif($type == self::MODEL_INSERT && isset($this->insertFields)) {
            $fields =   $this->insertFields;
        }elseif($type == self::MODEL_UPDATE && isset($this->updateFields)) {
            $fields =   $this->updateFields;
        }
        if(isset($fields)) {
            if(is_string($fields)) {
                $fields =   explode(',',$fields);
            }
            // 判断令牌验证字段
            if(C('TOKEN_ON'))   $fields[] = C('TOKEN_NAME');
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }
            }
        }

        // 数据自动验证
        if(!$this->autoValidation($data,$type)) return false;

        // 表单令牌验证
        if(!$this->autoCheckToken($data)) {
            $this->error = L('_TOKEN_ERROR_');
            return false;
        }

        // 验证完成生成数据对象
        if($this->autoCheckFields) { // 开启字段检测 则过滤非法字段数据
            $fields =   $this->getDbFields();
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }elseif(MAGIC_QUOTES_GPC && is_string($val)){
                    $data[$key] =   stripslashes($val);
                }
            }
        }

        // 创建完成对数据进行自动处理
        $this->autoOperation($data,$type);
        // 赋值当前数据对象
        $this->data =   $data;
        // 返回创建的数据以供其他调用
        return $data;
     }

    // 自动表单令牌验证
    // TODO  ajax无刷新多次提交暂不能满足
    public function autoCheckToken($data) {
        // 支持使用token(false) 关闭令牌验证
        if(isset($this->options['token']) && !$this->options['token']) return true;
        if(C('TOKEN_ON')){
            $name   = C('TOKEN_NAME');
            if(!isset($data[$name]) || !isset($_SESSION[$name])) { // 令牌数据无效
                return false;
            }

            // 令牌验证
            list($key,$value)  =  explode('_',$data[$name]);
            if($value && $_SESSION[$name][$key] === $value) { // 防止重复提交
                unset($_SESSION[$name][$key]); // 验证完成销毁session
                return true;
            }
            // 开启TOKEN重置
            if(C('TOKEN_RESET')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }

    /**
     * 使用正则验证数据
     * @access public
     * @param string $value  要验证的数据
     * @param string $rule 验证规则
     * @return boolean
     */
    public function regex($value,$rule) {
        $validate = array(
            'require'   =>  '/\S+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
        );
        // 检查是否有内置的正则表达式
        if(isset($validate[strtolower($rule)]))
            $rule       =   $validate[strtolower($rule)];
        return preg_match($rule,$value)===1;
    }

    /**
     * 自动表单处理
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    private function autoOperation(&$data,$type) {
        if(!empty($this->options['auto'])) {
            $_auto   =   $this->options['auto'];
            unset($this->options['auto']);
        }elseif(!empty($this->_auto)){
            $_auto   =   $this->_auto;
        }
        // 自动填充
        if(isset($_auto)) {
            foreach ($_auto as $auto){
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if(empty($auto[2])) $auto[2] =  self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    if(empty($auto[3])) $auto[3] =  'string';
                    switch(trim($auto[3])) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // 为空忽略
                            if($auto[1]===$data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if(isset($data[$auto[0]]) && false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    /**
     * 自动表单验证
     * @access protected
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return boolean
     */
    protected function autoValidation($data,$type) {
        if(!empty($this->options['validate'])) {
            $_validate   =   $this->options['validate'];
            unset($this->options['validate']);
        }elseif(!empty($this->_validate)){
            $_validate   =   $this->_validate;
        }
        // 属性验证
        if(isset($_validate)) { // 如果设置了数据自动验证则进行数据验证
            if($this->patchValidate) { // 重置验证错误信息
                $this->error = array();
            }
            foreach($_validate as $key=>$val) {
                // 验证因子定义格式
                // array(field,rule,message,condition,type,when,params)
                // 判断是否需要执行验证
                if(empty($val[5]) || $val[5]== self::MODEL_BOTH || $val[5]== $type ) {
                    if(0==strpos($val[2],'{%') && strpos($val[2],'}'))
                        // 支持提示信息的多语言 使用 {%语言定义} 方式
                        $val[2]  =  L(substr($val[2],2,-1));
                    $val[3]  =  isset($val[3])?$val[3]:self::EXISTS_VALIDATE;
                    $val[4]  =  isset($val[4])?$val[4]:'regex';
                    // 判断验证条件
                    switch($val[3]) {
                        case self::MUST_VALIDATE:   // 必须验证 不管表单是否有设置该字段
                            if(false === $this->_validationField($data,$val)) 
                                return false;
                            break;
                        case self::VALUE_VALIDATE:    // 值不为空的时候才验证
                            if('' != trim($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                            break;
                        default:    // 默认表单存在该字段就验证
                            if(isset($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                    }
                }
            }
            // 批量验证的时候最后返回错误
            if(!empty($this->error)) return false;
        }
        return true;
    }

    /**
     * 验证表单字段 支持批量验证
     * 如果批量验证返回错误的数组信息
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    protected function _validationField($data,$val) {
        if($this->patchValidate && isset($this->error[$val[0]]))
            return ; //当前字段已经有规则验证没有通过
        if(false === $this->_validationFieldItem($data,$val)){
            if($this->patchValidate) {
                $this->error[$val[0]]   =   $val[2];
            }else{
                $this->error            =   $val[2];
                return false;
            }
        }
        return ;
    }

    /**
     * 根据验证因子验证字段
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    protected function _validationFieldItem($data,$val) {
        switch(strtolower(trim($val[4]))) {
            case 'function':// 使用函数进行验证
            case 'callback':// 调用方法进行验证
                $args = isset($val[6])?(array)$val[6]:array();
                if(is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                if(is_array($val[0])){
                    // 支持多个字段验证
                    foreach($val[0] as $field)
                        $_data[$field] = $data[$field];
                    array_unshift($args, $_data);
                }else{
                    array_unshift($args, $data[$val[0]]);
                }
                if('function'==$val[4]) {
                    return call_user_func_array($val[1], $args);
                }else{
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm': // 验证两个字段是否相同
                return $data[$val[0]] == $data[$val[1]];
            case 'unique': // 验证某个值是否唯一
                if(is_string($val[0]) && strpos($val[0],','))
                    $val[0]  =  explode(',',$val[0]);
                $map = array();
                if(is_array($val[0])) {
                    // 支持多个字段验证
                    foreach ($val[0] as $field)
                        $map[$field]   =  $data[$field];
                }else{
                    $map[$val[0]] = $data[$val[0]];
                }
                if(!empty($data[$this->getPk()])) { // 完善编辑的时候验证唯一
                    $map[$this->getPk()] = array('neq',$data[$this->getPk()]);
                }
                if($this->where($map)->find())   return false;
                return true;
            default:  // 检查附加规则
                return $this->check($data[$val[0]],$val[1],$val[4]);
        }
    }

    /**
     * 验证数据 支持 in between equal length regex expire ip_allow ip_deny
     * @access public
     * @param string $value 验证数据
     * @param mixed $rule 验证表达式
     * @param string $type 验证方式 默认为正则验证
     * @return boolean
     */
    public function check($value,$rule,$type='regex'){
        $type   =   strtolower(trim($type));
        switch($type) {
            case 'in': // 验证是否在某个指定范围之内 逗号分隔字符串或者数组
            case 'notin':
                $range   = is_array($rule)? $rule : explode(',',$rule);
                return $type == 'in' ? in_array($value ,$range) : !in_array($value ,$range);
            case 'between': // 验证是否在某个范围
            case 'notbetween': // 验证是否不在某个范围            
                if (is_array($rule)){
                    $min    =    $rule[0];
                    $max    =    $rule[1];
                }else{
                    list($min,$max)   =  explode(',',$rule);
                }
                return $type == 'between' ? $value>=$min && $value<=$max : $value<$min || $value>$max;
            case 'equal': // 验证是否等于某个值
            case 'notequal': // 验证是否等于某个值            
                return $type == 'equal' ? $value == $rule : $value != $rule;
            case 'length': // 验证长度
                $length  =  mb_strlen($value,'utf-8'); // 当前数据长度
                if(strpos($rule,',')) { // 长度区间
                    list($min,$max)   =  explode(',',$rule);
                    return $length >= $min && $length <= $max;
                }else{// 指定长度
                    return $length == $rule;
                }
            case 'expire':
                list($start,$end)   =  explode(',',$rule);
                if(!is_numeric($start)) $start   =  strtotime($start);
                if(!is_numeric($end)) $end   =  strtotime($end);
                return NOW_TIME >= $start && NOW_TIME <= $end;
            case 'ip_allow': // IP 操作许可验证
                return in_array(get_client_ip(),explode(',',$rule));
            case 'ip_deny': // IP 操作禁止验证
                return !in_array(get_client_ip(),explode(',',$rule));
            case 'regex':
            default:    // 默认使用正则验证 可以使用验证类中定义的验证名称
                // 检查附加规则
                return $this->regex($value,$rule);
        }
    }

    /**
     * SQL查询
     * @access public
     * @param string $sql  SQL指令
     * @param mixed $parse  是否需要解析SQL
     * @return mixed
     */
    public function query($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->query($sql);
    }

    /**
     * 执行SQL语句
     * @access public
     * @param string $sql  SQL指令
     * @param mixed $parse  是否需要解析SQL
     * @return false | integer
     */
    public function execute($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->execute($sql);
    }

    /**
     * 解析SQL语句
     * @access public
     * @param string $sql  SQL指令
     * @param boolean $parse  是否需要解析SQL
     * @return string
     */
    protected function parseSql($sql,$parse) {
        // 分析表达式
        if(true === $parse) {
            $options =  $this->_parseOptions();
            $sql    =   $this->db->parseSql($sql,$options);
        }elseif(is_array($parse)){ // SQL预处理
            $parse  =   array_map(array($this->db,'escapeString'),$parse);
            $sql    =   vsprintf($sql,$parse);
        }else{
            $sql    =   strtr($sql,array('__TABLE__'=>$this->getTableName(),'__PREFIX__'=>C('DB_PREFIX')));
        }
        $this->db->setModel($this->name);
        return $sql;
    }

    /**
     * 切换当前的数据库连接
     * 字段类型检测
     * @access public
     * @param integer $linkNum  连接序号
     * @param mixed $config  数据库连接信息
     * @param boolean $force 强制重新连接
     * @return Model
     */
    public function db($linkNum='',$config='',$force=false) {
    	/*
    	 * $linkNum>>>'',$this->db>>>NULL
    	 */
        if('' === $linkNum && $this->db) {
            return $this->db;
        }

        static $_db = array();  //将数据库连接放在了静态数组中。静态变量在函数中初始化一次后，再执行是不会重新初始化的。程序离开静态变量的函数后，静态变量是不会消失的。
        if(!isset($_db[$linkNum]) || $force ) {
            // 创建一个新的实例
            //$config>>>''
            if(!empty($config) && is_string($config) && false === strpos($config,'/')) { // 支持读取配置参数
                $config  =  C($config);
            }
            $_db[$linkNum]            =    Db::getInstance($config);
        }elseif(NULL === $config){
            $_db[$linkNum]->close(); // 关闭数据库连接
            unset($_db[$linkNum]);
            return ;
        }
        // 切换数据库连接
        $this->db   =    $_db[$linkNum];
        $this->_after_db();  //_after_db()是空的，可以在子模型类中实现。
        // 字段检测
        //name是模型名
        //autoCheckFields>>>true，知否自动检测数据表字段信息
        if(!empty($this->name) && $this->autoCheckFields)    $this->_checkTableInfo();
        return $this;
    }
    // 数据库切换后回调方法
    protected function _after_db() {}

    /**
     * 得到当前的数据对象名称
     * @access public
     * @return string
     */
    public function getModelName() {
        if(empty($this->name)){  //又是一个单例功能
        	//get_class——返回一个对象的类名
        	//$name是截取掉模型类名后面的'Model'，是'Think\'
        	//$this>>>object(Think\Model)
            $name = substr(get_class($this),0,-5);
            if ( $pos = strrpos($name,'\\') ) {//有命名空间
                $this->name = substr($name,$pos+1);  //none
            }else{
                $this->name = $name;
            }
        }
        return $this->name;
    }

    /**
     * 得到完整的数据表名
     * 带有数据库名、表前缀的表名
     * 1.通过属性tableName和name得到带表前缀的表名
     * 2.连接上数据库名，并返回该字符串
     * @access public
     * @return string
     */
    public function getTableName() {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';	//debug>>>这个改为$tablePrefix更好，因为它就是表前缀，通过它来判断用哪个属性获取表名
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;	//没有表前缀的表名
            }else{
                $tableName .= parse_name($this->name);	//name是模型名，带表前缀的表名
            }
            $this->trueTableName    =   strtolower($tableName);	//将表名转换成小写。带表前缀
        }
        return (!empty($this->dbName)?$this->dbName.'.':'').$this->trueTableName;	//连接上数据库名
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {
        $this->commit();
        $this->db->startTrans();
        return ;
    }

    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback() {
        return $this->db->rollback();
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 返回数据库的错误信息
     * @access public
     * @return string
     */
    public function getDbError() {
        return $this->db->getError();
    }

    /**
     * 返回最后插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID() {
        return $this->db->getLastInsID();
    }

    /**
     * 返回最后执行的sql语句
     * @access public
     * @return string
     */
    public function getLastSql() {
        return $this->db->getLastSql($this->name);
    }
    // 鉴于getLastSql比较常用 增加_sql 别名
    public function _sql(){
        return $this->getLastSql();
    }

    /**
     * 获取主键名称
     * @access public
     * @return string
     */
    public function getPk() {
        return $this->pk;
    }

    /**
     * 获取数据表字段信息
     * @access public
     * @return array
     */
    public function getDbFields(){
        if(isset($this->options['table'])) {// 动态指定表名
            $array      =   explode(' ',$this->options['table']);
            $fields     =   $this->db->getFields($array[0]);
            return  $fields?array_keys($fields):false;
        }
        if($this->fields) {
            $fields     =  $this->fields;
            unset($fields['_type'],$fields['_pk']);
            return $fields;
        }
        return false;
    }

    /**
     * 设置数据对象值
     * @access public
     * @param mixed $data 数据
     * @return Model
     */
    public function data($data=''){
        if('' === $data && !empty($this->data)) {
            return $this->data;
        }
        if(is_object($data)){
            $data   =   get_object_vars($data);
        }elseif(is_string($data)){
            parse_str($data,$data);
        }elseif(!is_array($data)){
            E(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 指定当前的数据表
     * @access public
     * @param mixed $table
     * @return Model
     */
    public function table($table) {
        $prefix =   $this->tablePrefix;
        if(is_array($table)) {
            $this->options['table'] =   $table;
        }elseif(!empty($table)) {
            //将__TABLE_NAME__替换成带前缀的表名
            $table  = preg_replace_callback("/__([A-Z_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $table);
            $this->options['table'] =   $table;
        }
        return $this;
    }

    /**
     * 查询SQL组装 join
     * @access public
     * @param mixed $join
     * @param string $type JOIN类型
     * @return Model
     */
    public function join($join,$type='INNER') {
        $prefix =   $this->tablePrefix;
        if(is_array($join)) {
            foreach ($join as $key=>&$_join){
                $_join  =   preg_replace_callback("/__([A-Z_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $_join);
                $_join  =   false !== stripos($_join,'JOIN')? $_join : $type.' JOIN ' .$_join;
            }
            $this->options['join']      =   $join;
        }elseif(!empty($join)) {
            //将__TABLE_NAME__字符串替换成带前缀的表名
            $join  = preg_replace_callback("/__([A-Z_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $join);
            $this->options['join'][]    =   false !== stripos($join,'JOIN')? $join : $type.' JOIN '.$join;
        }
        return $this;
    }

    /**
     * 查询SQL组装 union
     * @access public
     * @param mixed $union
     * @param boolean $all
     * @return Model
     */
    public function union($union,$all=false) {
        if(empty($union)) return $this;
        if($all) {
            $this->options['union']['_all']  =   true;
        }
        if(is_object($union)) {
            $union   =  get_object_vars($union);
        }
        // 转换union表达式
        if(is_string($union) ) {
            $prefix =   $this->tablePrefix;
            //将__TABLE_NAME__字符串替换成带前缀的表名
            $options  = preg_replace_callback("/__([A-Z_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $union);
        }elseif(is_array($union)){
            if(isset($union[0])) {
                $this->options['union']  =  array_merge($this->options['union'],$union);
                return $this;
            }else{
                $options =  $union;
            }
        }else{
            E(L('_DATA_TYPE_INVALID_'));
        }
        $this->options['union'][]  =   $options;
        return $this;
    }

    /**
     * 查询缓存
     * @access public
     * @param mixed $key
     * @param integer $expire
     * @param string $type
     * @return Model
     */
    public function cache($key=true,$expire=null,$type=''){
        if(false !== $key)
            $this->options['cache']  =  array('key'=>$key,'expire'=>$expire,'type'=>$type);
        return $this;
    }

    /**
     * 指定查询字段 支持字段排除
     * 通过getDbFields()来获取字段信息，如果$except=true，且获取字段信息失败，将'*'赋值给$fields
     * 排除字段，是通过array_diff()实现，如果传递过来的$field是字符串，使用explode(',', $fileds)来变成数组
     * @access public
     * @param mixed $field 数组或者由逗号分隔的字符串
     * @param boolean $except 是否排除
     * @return Model
     */
    public function field($field,$except=false){  //是bool，不是字符串的true。
        if(true === $field) {// 获取全部字段
            $fields     =  $this->getDbFields();
            $field      =  $fields?$fields:'*'; //如果获取字段信息失败，直接使用'*'。
        }elseif($except) {// 字段排除
            if(is_string($field)) {
                $field  =  explode(',',$field);
            }
            $fields     =  $this->getDbFields();
            $field      =  $fields?array_diff($fields,$field):$field;
        }
        $this->options['field']   =   $field;
        return $this;
    }

    /**
     * 调用命名范围
     * 将命名范围对应的数组解析合并到数组中，然后和options属性合并并赋值给属性options,返回$this
     * 1.如果scope()没有传入参数，尝试去获取'default'对应的命名范围值，否则直接返回$this
     * 2.如果第一个参数，支持用逗号分割的多个命名范围。使用foreach()和array_merge()得到一个表达式$options数组。如果第二个参数是数组，直接与上一步得到的数组合并
     * 3.如果第一个参数是数组，当作命名范围的定义赋值给$options数组
     * 4.将$options的键名全部转换成小写形式，用array_merge()于$this->options合并
     * 5.返回$this
     * @access public
     * @param mixed $scope 命名范围名称 支持多个 和直接定义
     * @param array $args 参数
     * @return Model
     */
    public function scope($scope='',$args=NULL){
        if('' === $scope) {	//如果有默认命名范围，就使用它；否则，返回$this，即scope()连贯操作无效了。
            if(isset($this->_scope['default'])) {	//可以定义一个标识符是'default'的命名范围，做默认命名范围使用
                // 默认的命名范围
                $options    =   $this->_scope['default'];
            }else{
                return $this;
            }
        }elseif(is_string($scope)){ // 支持多个命名范围调用 用逗号分割
            $scopes         =   explode(',',$scope);
            $options        =   array();
            foreach ($scopes as $name){
                if(!isset($this->_scope[$name])) continue;
                $options    =   array_merge($options,$this->_scope[$name]);
            }
            if(!empty($args) && is_array($args)) {	//scope('test', array('order'=> 'id'))
                $options    =   array_merge($options,$args);
            }
        }elseif(is_array($scope)){ // 直接传入命名范围定义。这样使用没有多大意义，不如直接使用TP的连贯操作其他关键字
            $options        =   $scope;
        }
        
        if(is_array($options) && !empty($options)){
        	//array_change_key_case——返回字符串键名全为小写或大写的数组，默认转化成小写。不改变数字索引。
        	//将各个命名空间解析成数组，存放到参数表达式中。在其他$this->options的操作中，都是使用array_merge()，所以会出现后面覆盖前面连贯操作条件的情况。
            $this->options  =   array_merge($this->options,array_change_key_case($options));
        }
        return $this;
    }

    /**
     * 指定查询条件 支持安全过滤
     * 实际上是给options['where']赋值
     * 可以使用对象作为参数
     * 传入''无效
     * 1.将对象或字符串参数转换成数组形式，其中字符串参数赋值给'_string'，
     * 2.将数组赋值给属性optinos['where']，支持多个where()，用array_merge()合并
     * @access public
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     * @return Model
     */
    public function where($where,$parse=null){
        if(!is_null($parse) && is_string($where)) {
            if(!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map(array($this->db,'escapeString'),$parse);
            $where =   vsprintf($where,$parse);
        }elseif(is_object($where)){  //$where还可以是对象
            $where  =   get_object_vars($where);
        }
        if(is_string($where) && '' != $where){
            $map    =   array();	//作为临时存储变量
            $map['_string']   =   $where;  //将字符串参数赋值给了'_string'
            $where  =   $map;
        }        
        if(isset($this->options['where'])){	//支持多个where()，使用的是array_merge()，来合并where数组
            $this->options['where'] =   array_merge($this->options['where'],$where);
        }else{
            $this->options['where'] =   $where;
        }
        
        return $this;
    }

    /**
     * 指定查询数量
     * 将其实位置和查询数量拼接成由逗号分隔的字符串，给属性options['limit']赋值
     * 不一定要有长度
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return Model
     */
    public function limit($offset,$length=null){
        $this->options['limit'] =   is_null($length)?$offset:$offset.','.$length;
        return $this;
    }

    /**
     * 指定分页
     * @access public
     * @param mixed $page 页数
     * @param mixed $listRows 每页数量
     * @return Model
     */
    public function page($page,$listRows=null){
        $this->options['page'] =   is_null($listRows)?$page:$page.','.$listRows;
        return $this;
    }

    /**
     * 查询注释
     * @access public
     * @param string $comment 注释
     * @return Model
     */
    public function comment($comment){
        $this->options['comment'] =   $comment;
        return $this;
    }

    /**
     * 参数绑定
     * @access public
     * @param string $key  参数名
     * @param mixed $value  绑定的变量及绑定参数
     * @return Model
     */
    public function bind($key,$value=false) {
        if(is_array($key)){
            $this->options['bind'] =    $key;
        }else{
            $num =  func_num_args();
            if($num>2){
                $params =   func_get_args();
                array_shift($params);
                $this->options['bind'][$key] =  $params;
            }else{
                $this->options['bind'][$key] =  $value;
            }        
        }
        return $this;
    }

    /**
     * 设置模型的属性值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return Model
     */
    public function setProperty($name,$value) {
        if(property_exists($this,$name))
            $this->$name = $value;
        return $this;
    }

}