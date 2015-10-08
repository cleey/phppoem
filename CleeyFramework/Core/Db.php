<?php
namespace Cleey;

class Db{

	private static $_ins;
	protected $_conn = null;

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
		$this->_conn  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
	}

	function query($sql){
		$time = microtime(1);
		if( is_null($this->_conn) ) $this->connect();
		$re = $this->_conn->query($sql);
		$time = microtime(1)-$time;
		Log::push("{$sql} [{$time}ms]");
		if( $re == false ) return null;
		return $re->fetchAll(\PDO::FETCH_ASSOC);
	}
	function select($sql,$bind){ return $this->exec($sql,$bind,'select'); }
	function insert($sql,$bind){ return $this->exec($sql,$bind,'insert'); }
	function update($sql,$bind){ return $this->exec($sql,$bind,'update'); }
	function delete($sql,$bind){ return $this->exec($sql,$bind,'delete'); }

	function exec($sql,$bind,$flag=''){
		$time = microtime(1);
		if( is_null($this->_conn) ) $this->connect();

		$pre = $this->_conn->prepare($sql);
		foreach ($bind as $k => $v) $pre->bindValue($k,$v);
		$re  = $pre->execute();
		
		$time = microtime(1)-$time;
		Log::push("{$sql} [{$time}ms]");

		switch ($flag) {
			case 'insert': return $this->_conn->lastInsertId(); break;
			case 'update': return $pre->rowCount(); break;
			case 'delete': return $pre->rowCount(); break;
			case 'select': return $pre->fetchAll(\PDO::FETCH_ASSOC); break;
			default: break;
		}
	}
}

?>