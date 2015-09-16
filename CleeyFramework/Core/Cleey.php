<?php 
namespace Cleey;

class Cleey{
	
	private static $instance = array(); // 实例化的类和方法
	private static $time = array(); // 计时
	
	static function start(){
		spl_autoload_register('\Cleey\Cleey::autoload'); // 自动加载，没有找到本地类的
		register_shutdown_function('\Cleey\Cleey::appFatal'); // 错误和异常处理
		set_error_handler('\Cleey\Cleey::appError');
		set_exception_handler('\Cleey\Cleey::appException');

		self::$time['CLEEY_TIME'] = microtime(1);
		self::route(); // 路由管理
		self::func();  // 函数库
		self::conf();  // 配置文件
		self::exec();  // 执行操作
		self::end();   // 结束
	}

	// 没找到类，自动到这里加载
	static function autoload($class){
		$class = str_replace('\\', '/', $class);
		// 命名空间
		if( strstr($class,'/',true) == 'Cleey' ) $file = CORE_PATH.strstr($class,'/').'.php';
		else $file = APP_PATH.$class.'.php';

		if( !is_file($file) ) CO( "自动加载Cleey::autoload ：找不到类 ".$file );
		include $file;
	}

	// 存储已经实例化的类以及方法
	static function instance($class,$method=''){
		// 声明类
		if( !isset(self::$instance[$class]) ) self::$instance[$class] = new $class;
		$key = $method == '' ? $class : $class.'\\'.$method;
		// 声明方法
		if( !isset(self::$instance[$key]) )
			self::$instance[$key] = $method=='' ? $obj : call_user_func(array(&self::$instance[$class], $method));
		return self::$instance[$key];
	}

	// 路由 获取请求模块，控制器，方法
	static function route(){
		$url = array();
		if( isset($_SERVER['PATH_INFO']) ){
			$_URL = preg_replace('/index.php/', '', $_SERVER['PATH_INFO']); // 去除index.php
			$_EXT = pathinfo($_URL,PATH_INFO_EXTENSION);  // 获取url后缀
			if( $_EXT ) $_URL = preg_replace('/'.$_EXT.'$/i', '', $_URL); // 删除url后缀
			$url = explode('/', $_URL); // /Home/Index/index
		}
		define('CF_MODULE' , !empty($url[1]) ? ucfirst($url[1]) : 'Home');
		define('CF_CLASS'  , !empty($url[2]) ? ucfirst($url[2]) : 'Index');
		define('CF_METHOD' , !empty($url[3]) ? $url[3] : 'index');

		define('__APP__' , $_SERVER['SCRIPT_NAME']); // 项目入口文件 */index.php
		define('__ROOT__' , dirname(__APP__));  // 顶级web目录
	}

	// 加载方法
	static function func(){
		include CORE_FUNC; // 核心库
		include APP_FUNC ; // App公共
		include APP_PATH.CF_MODULE.'/Common/function.php'; // 请求模块
	}

	// 加载配置
	static function conf(){
		C(include CORE_CONF);  // 核心库
		C(include APP_CONF );  // App公共
		C(include APP_PATH.CF_MODULE.'/Common/config.php'); // 请求模块
	}

	// 加载配置
	static function exec(){
		self::$time['APP_TIME'] = microtime(1);

		self::instance(CF_MODULE.'\\Controller\\'.CF_CLASS, CF_METHOD); // 执行操作

		self::$time['APP_TIME'] = microtime(1) - self::$time['APP_TIME'];
	}

	// 结束
	static function end(){
		self::$time['CLEEY_TIME'] = microtime(1) - self::$time['CLEEY_TIME'];

		echo '<br>=============== SYS ===============';
		echo "<br>-- 总共时间：",self::$time['CLEEY_TIME'],' s';
		echo "<br>-- 框架加载：",self::$time['CLEEY_TIME'] - self::$time['APP_TIME'],' s';
		echo "<br>-- App 时间：",self::$time['APP_TIME'],' s';
		echo "<br>-- 内存使用：",memory_get_usage()/1024/1024,' MB';
		Log::down();
	}

	// 接受PHP内部回调异常处理
	static function appException($e){
		$err = array();
		$err['message'] = $e->getMessage();
		$trace        = $e->getTrace();
		if( 'E' == $trace[0]['function'] ){
			$err['file'] = $trace[0]['file'];
			$err['line'] = $trace[0]['line'];
		}else{
			$err['file'] = $e->getFile();
			$err['line'] = $e->getLine();
		}
		$err['trace'] = $e->getTraceAsString();

		Log::push($err['message'],Log::ERR);
		self::halt($err);
	}

	// 自定义错误处理
	static function appError($errno,$errstr,$errfile,$errline){
		$errStr = "$errstr $errfile 第 $errline 行.";
		Log::push($errStr,Log::ERR);

		$haltArr = array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR);
		if( in_array($errno, $haltArr) )
			self::halt($errStr);
	}

	// 致命错误捕获
	static function appFatal(){
		$e = error_get_last();
		$haltArr = array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR);
		if( $e && in_array($e['type'], $haltArr) ){
			self::halt($e);
		}
	}


	// 错误输出
	static function halt($err){
		$e = array();

		if( !is_array($err) ){
			$trace = debug_backtrace();
			$e['message']  = $err;
			$e['file'] = $trace[0]['file'];
			$e['line'] = $trace[0]['line'];
			ob_start();
			debug_print_backtrace();
			$e['trace']= ob_get_clean();
		}else $e = $err;

		if( PHP_SAPI == 'cli' ) exit( iconv('UTF-8','gbk',$e['message']).PHP_EOL.'File: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']);

		// echo 'halt';
		include CORE_PATH.'Tpl/exception.php';
		exit;
	}

}

 ?>