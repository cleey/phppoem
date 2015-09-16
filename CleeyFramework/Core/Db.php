<?php
namespace Cleey;

class Db{

	private static $_ins;
	private $db = null;

	static function getIns(){
		if( !(self::$_ins instanceof self) )
			self::$_ins = new self();
		return self::$_ins;
	}

	private function connect(){
		$type = C('DB_TYPE');
		$host = C('DB_HOST');
		$name = C('DB_NAME');
		$user = C('DB_USER');
		$pass = C('DB_PASS');
		$dsn = "{$type}:host={$host};dbname={$name};charset=utf8";
		$this->db  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
	}

	public function query($sql){
		$time = microtime(1);
		if( is_null($this->db) ) $this->connect();
		$re = $this->db->query($sql);
		$time = microtime(1)-$time;
		Log::push("{$sql} [{$time}ms]");
		return $re->fetchAll();
	}
}

?>