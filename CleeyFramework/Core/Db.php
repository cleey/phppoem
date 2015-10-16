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
		T('cleey_db_exec');
		$this->_conn  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
		$time = number_format(T('cleey_db_exec',1)*1000,2);

		Log::trace('SQL',"PDO连接 [{$time}ms]");
	}

	function query($sql){
		T('cleey_db_exec');
		if( is_null($this->_conn) ) $this->connect();
		$re = $this->_conn->query($sql);
		$time = number_format(T('cleey_db_exec',1)*1000,2);
		Log::trace('SQL',"{$sql} [{$time}ms]");
		if( $re == false ) return null;
		return $re->fetchAll(\PDO::FETCH_ASSOC);
	}
	function select($sql,$bind){ return $this->exec($sql,$bind,'select'); }
	function insert($sql,$bind){ return $this->exec($sql,$bind,'insert'); }
	function update($sql,$bind){ return $this->exec($sql,$bind,'update'); }
	function delete($sql,$bind){ return $this->exec($sql,$bind,'delete'); }

	function exec($sql,$bind,$flag=''){
		T('cleey_db_exec');
		if( is_null($this->_conn) ) $this->connect();

		$pre = $this->_conn->prepare($sql);
		foreach ($bind as $k => $v) $pre->bindValue($k,$v);
		$re  = $pre->execute();
		
		$time = number_format(T('cleey_db_exec',1)*1000,2);
		Log::trace('SQL',"{$sql} [{$time}ms]");

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