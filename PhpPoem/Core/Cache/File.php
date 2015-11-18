<?php 
namespace Poem\Cache;

class File{
	protected $_prefix;

	function __construct(){
		$path = APP_PATH.config('runtime_path');
		if( !is_dir($path) ) mkdir($path);
		$this->_prefix = $path;
	}

	public function get($key){
		$key = $this->_prefix.$key.'.php';
		$data= file_get_contents($key);
		$json= json_decode( $value, true );
		return $json === NULL ? $data : $json;
	}

	public function set($key,$value,$append=0){
		$key = $this->_prefix.$key.'.php';
		$jsonData  = json_decode( $value, true );
		if($append == 0)file_put_contents($key, $value);
		else file_put_contents($key, $value,FILE_APPEND);
		return $key;
	}

	public function del($key){
		$key = $this->_prefix.$key.'.php';
		unlink($key);
	}

	public function close(){
		return 1;
	}

}

 ?>