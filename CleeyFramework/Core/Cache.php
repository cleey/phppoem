<?php 
namespace Cleey;

class Cache{
	
	static function get($key){
		return file_get_contents($key);
	}

	static function set($key,$value,$append=0){
		if($append == 0)file_put_contents($key, $value);
		else file_put_contents($key, $value,FILE_APPEND);
	}

}

 ?>