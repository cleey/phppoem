<?php
namespace poem;
class load {
	static function register() {
		spl_autoload_register('\poem\load::autoload');
	}

	// 没找到类，自动到这里加载
	static function autoload($class) {
		$class = strtolower(str_replace('\\', '/', $class));
		// 命名空间
		if (strstr($class, '/', true) == 'poem') {
			$file = CORE_PATH . trim(strstr($class, '/'), '/') . '.php';
		} elseif (strstr($class, '/', true) == 'lib') {
			$file = APP_PATH . '../' . $class . '.php';
		} else {
			$file = APP_PATH . $class . '.php';
		}

		if (!is_file($file)) {
			app::halt("自动加载：找不到类 " . $class);
		}

		include $file;
	}

	// 存储已经实例化的类以及方法
	static function instance($class) {
		static $ins = [];
		if (!isset($ins[$class])) {
			$ins[$class] = new $class;
		}

		return $ins[$class];
	}

	// 扩展包引入
	static function vendor($class, $ext = '.php') {
		static $_file = array();
		// if( class_exists($class) ) return true;vendor
		if (isset($_file[$class])) {
			return true;
		}

		$file = VENDOR_PATH . $class . $ext;
		if (!is_file($file)) {\poem\app::halt('文件不存在: ' . $file);}
		$_file[$class] = true;
		require $file;
	}

	// controller
	static function controller($class, $module = POEM_MODULE) {
		$name = "$module\\controller\\$class";
		return self::instance($name);
	}

}