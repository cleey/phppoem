<?php 
namespace Cleey;

class Cleey{
	
	private static $instance = array(); // 实例化的类和方法
	public static $time = array(); // 计时
	
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

		if( !is_file($file) ) \Cleey\Cleey::halt( "自动加载Cleey::autoload ：找不到类 ".$file );
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
			$_EXT = pathinfo($_URL,PATHINFO_EXTENSION);  // 获取url后缀
			if( $_EXT ) $_URL = preg_replace('/'.$_EXT.'$/i', '', $_URL); // 删除url后缀
			$url = explode('/', $_URL); // /Home/Index/index
		}
		define('CF_MODULE' , !empty($url[1]) ? ucfirst($url[1]) : 'Home');
		define('CF_CLASS'  , !empty($url[2]) ? ucfirst($url[2]) : 'Index');
		define('CF_METHOD' , !empty($url[3]) ? $url[3] : 'index');

		define('__APP__' , $_SERVER['SCRIPT_NAME']); // 项目入口文件 */index.php
		define('__ROOT__' , dirname(__APP__));  // 顶级web目录
		define('CF_CLASS_URL' , $_SERVER['SCRIPT_NAME'].'/'.CF_MODULE.'/'.CF_CLASS);  // class url
		define('CF_METHOD_URL' , $_SERVER['SCRIPT_NAME'].'/'.CF_MODULE.'/'.CF_CLASS.'/'.CF_METHOD);  // method url
	}

	// 加载方法
	static function func(){
		include CORE_FUNC; // 核心库
		include APP_FUNC ; // App公共
		$file = APP_PATH.CF_MODULE.'/Common/function.php';
		if( is_file($file) ) include $file; // 请求模块
	}

	// 加载配置
	static function conf(){
		C(include CORE_CONF);  // 核心库
		C(include APP_CONF );  // App公共
		$file = APP_PATH.CF_MODULE.'/Common/config.php';
		if( is_file($file) ) C(include $file); // 请求模块
	}

	// 加载配置
	static function exec(){
		if( C('SESSION_AUTO_START') ){ session('[start]') ; }
		self::$time['APP_TIME'] = microtime(1);

		self::instance(CF_MODULE.'\\Controller\\'.CF_CLASS, CF_METHOD); // 执行操作

		self::$time['APP_TIME'] = microtime(1) - self::$time['APP_TIME'];
	}

	// 结束
	static function end(){
		// return;
		self::$time['CLEEY_TIME'] = microtime(1) - self::$time['CLEEY_TIME'];

		Log::trace('SYS',"总共时间：".self::$time['CLEEY_TIME'].' s' );
		Log::trace('SYS',"吞吐量  ：".number_format(1/self::$time['CLEEY_TIME'],2).' req/s' );
		Log::trace('SYS',"框架加载：".(self::$time['CLEEY_TIME'] - self::$time['APP_TIME']).' s' );
		Log::trace('SYS',"App时间：".self::$time['APP_TIME'].' s' );
		Log::trace('SYS',"内存使用：".(memory_get_usage()/1024/1024).' MB' );
		// Log::down();
		Log::show();
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

	static public function logo(){
        return 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyBpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBXaW5kb3dzIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjVERDVENkZGQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjVERDVENzAwQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NURENUQ2RkRCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NURENUQ2RkVCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5fx6IRAAAMCElEQVR42sxae3BU1Rk/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';
    }

}

 ?>