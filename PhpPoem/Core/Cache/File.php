<?php 
namespace Poem\Cache;

class File{
	
	public function has($key){
		$key = APP_RUNTIME_PATH.$key.'.php';
		return is_file($key) ? $key : false;
	}

	public function get($key){
		$key = APP_RUNTIME_PATH.$key.'.php';
		if( !is_file($key) ) return false;
		$data= file_get_contents($key);
		$json= json_decode( $value, true );
		return $json === NULL ? $data : $json;
	}

	public function set($key,$value,$append=0){
		$key = APP_RUNTIME_PATH.$key.'.php';
		$dir = dirname($key);
		if( !is_dir($dir) ) mkdir($dir,0775,true);
		$jsonData  = json_decode( $value, true );
		if($append == 0) $re = file_put_contents($key, $value);
		else $re = file_put_contents($key, $value,FILE_APPEND);
		if( !$re ) e('文件写入失败：'.$path_or_append);
		return $key;
	}

	public function del($key){
		$key = APP_RUNTIME_PATH.$key.'.php';
		if( !is_file($key) ) return false;
		unlink($key);
	}

	public function close(){
		return 1;
	}

}

 ?>