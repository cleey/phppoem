<?php 
namespace Poem\Cache;

class File{
	
	public function has($key){
		$key = APP_RUNTIME_PATH.$key.'.php';
		return is_file($key) ? $key : false;
	}

	public function get($key,$append){
		$key = APP_RUNTIME_PATH.$key.'.php';
		if( !is_file($key) ) return false;
		if( $append === -1 ) return file_get_contents($key);
		$data = file_get_contents($key);
		$json= unserialize( $data );
		return $json === NULL ? $data : $json;
	}

	public function set($key,$value,$append=0){
		$key = APP_RUNTIME_PATH.$key.'.php';
		$dir = dirname($key);
		if( !is_dir($dir) ) mkdir($dir,0775,true);

		if( $append === -1 ){
			$re  = file_put_contents($key, $value);
		}elseif( $append === -2 ){
			$re  = file_put_contents($key, $value,FILE_APPEND);
		}else{
			$value  = serialize($value);
			if($append == 0) $re = file_put_contents($key, $value);
			else $re = file_put_contents($key, $value,FILE_APPEND);
		}

		if( !$re ) error_log('文件写入失败：'.$key);
		return $key;
	}

	public function del($key){
		$key = APP_RUNTIME_PATH.$key.'.php';
		if( !is_file($key) ) return false;
		unlink($key);
	}
}

 ?>