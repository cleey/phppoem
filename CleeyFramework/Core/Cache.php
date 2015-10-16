<?php 
namespace Cleey;

class Cache{

	static $_ins = array();
	static function getIns($type='',$option=array()){
		if(empty($type))  $type = C('CACHE_TYPE') ? : 'File';
		// if( !isset(self::$_ins[$class]) ){
		if( !isset(self::$_ins[$type]) ){
			$class = '\\Cleey\\Cache\\'.ucwords(strtolower($type));
			$option = is_array($option) ? $option : array();
			self::$_ins[$type] = new $class($option);
		}
		return self::$_ins[$type];
	}

	static function delIns($type=''){
		if(empty($type))  $type = C('CACHE_TYPE') ? : 'File';
		if( isset(self::$_ins[$class]) ){
			self::$_ins[$class]->close();
			unset(self::$_ins[$class]);
		}
	}

}

 ?>