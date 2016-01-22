<?php
namespace poem;
class Load{
	static function register(){
        spl_autoload_register('\poem\load::autoload');
    }

	// 没找到类，自动到这里加载
	static function autoload($class){
		$class = strtolower(str_replace('\\', '/', $class));
		// 命名空间
		if( strstr($class,'/',true) == 'poem' ) $file = CORE_PATH.trim(strstr($class,'/'),'/').'.php';
		else $file = APP_PATH.$class.'.php';
		if( !is_file($file) ) app::halt( "自动加载：找不到类 ".$file );
		include $file;
	}

	// 存储已经实例化的类以及方法
	static function instance($class,$method=''){
		static $ins = [];
		// 声明类
		if( !isset($ins[$class]) ){
			if( !class_exists($class) ) throw new Exception('class not find: '.$class);
			$ins[$class] = new $class;
		}
		$key = $class.$method;
		// 声明方法
		if( !isset($ins[$key]) ){
			$ins[$key] = $method=='' ? $obj : call_user_func(array(&$ins[$class], $method));
		}

		return $ins[$key];
	}

	// 扩展包引入
	static function vendor($class,$ext='.php'){
		static $_file = array();
		if( class_exists($class) ) return true;
		if( isset($_file[$class]) ) return true;
		$file = VENDOR_PATH.$class.$ext;
		if( !is_file($file) ){\poem\app::halt('文件不存在: '.$file);}
		$_file[$class] = true;
		require $file;
	}

	// controller
	static function controller($class,$module=POEM_MODULE){
		static $ctrl = [];
		$name = "$module\\controller\\$class";
		if( !isset($ctrl[$name]) ) $ctrl[$name] = new $name;
		return $ctrl[$name];
	}

	static function func($class, $vars = []){
        $info   = pathinfo($url);
        $func = $info['basename'];
        $ctrl = '.' != $info['dirname'] ? $info['dirname'] : POEM_CTRL;
        $class  = self::controller($ctrl);

        if (is_string($vars)) parse_str($vars, $vars);
        return call_user_func_array([ & $class, $func], $vars);
    }


}


?>