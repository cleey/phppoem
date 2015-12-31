<?php 
namespace poem;

class cache{

	static $_ins = array();
	static function getIns($type='',$option=array()){
		if(empty($type))  $type = config('cache_type') ? : 'File';
		// if( !isset(self::$_ins[$class]) ){
		if( !isset(self::$_ins[$type]) ){
			$class = '\\poem\\cache\\'.strtolower($type);
			$option = is_array($option) ? $option : array();
			self::$_ins[$type] = new $class($option);
		}
		return self::$_ins[$type];
	}

	static function clear(){
		if(empty(self::$_ins)) return;

		foreach (self::$_ins as &$single)
			$single->close();
	}

}

 ?>