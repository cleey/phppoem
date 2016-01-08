<?php 
namespace Poem\Cache;

class Redis{
	public $_ins;
	protected $_option;
	function __construct($option=array()){
		$option = array_merge( array(
			'host'=>config('redis_host') ? : '127.0.0.1',
			'port'=>config('redis_port') ? : 6379,
			'expire'=>config('redis_expire') ? : 0,
			'auth'=>config('redis_auth') ? : 0,
			)
		,$option);
		$this->_option = $option;
		$this->_ins = new \Redis();
		$this->_ins->connect($option['host'],$option['port']);
	}
	
	public function get($key){
		$data= $this->_ins->get($key);
		$json= json_decode( $value, true );
		return $json === NULL ? $data : $json;
	}

	public function set($key,$value,$expire=null){
		if( is_null($expire) ) $expire = $this->_option['expire'];
		$value = is_object($value) || is_array($value) ? json_encode($value) : $value;
		return $this->_ins->set($key,$value,$expire);
	}

	public function del($key){
		return $this->_ins->del($key);
	}

	function __destruct(){ $this->_ins->close(); }

}

 ?>