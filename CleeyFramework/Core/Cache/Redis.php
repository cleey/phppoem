<?php 
namespace Cleey\Cache;

class Redis{
	public $_ins;
	protected $_option;
	function __construct($option=array()){
		$option = array_merge( array(
			'host'=>C('REDIS_HOST') ? : '127.0.0.1',
			'port'=>C('REDIS_PORT') ? : 6379,
			'expire'=>C('REDIS_EXPIRE') ? : 0,
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

	public function close(){
		return $this->_ins->close();
	}

}

 ?>