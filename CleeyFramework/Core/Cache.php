<?php 
namespace Cleey;

class Cache{

	static function getIns($type='',$option=array()){
		static $_ins = array();
		if(empty($type))  $type = C('CACHE_TYPE') ? : 'File';
		$class = '\\Cleey\\Cache\\'.ucwords(strtolower($type));
		if( !isset($_ins[$class]) ){
			$option = is_array($option) ? $option : array();
			$_ins[$class] = new $class($option);
		}
		return $_ins[$class];
	}

}

 ?>