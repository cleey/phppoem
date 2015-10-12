<?php 
namespace Cleey\Cache;

class File{
	
	public function get($key){
		return file_get_contents($key);
	}

	public function set($key,$value,$append=0){
		if($append == 0)file_put_contents($key, $value);
		else file_put_contents($key, $value,FILE_APPEND);
	}

	public function close(){
		return 1;
	}

}

 ?>