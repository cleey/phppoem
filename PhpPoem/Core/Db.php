<?php
namespace Poem;

class Db{

	private static $_ins;
	protected $_conn = null;

	static function getIns(){
		if( !(self::$_ins instanceof self) )
			self::$_ins = new self();
		return self::$_ins;
	}

	private function connect(){
		$type = config('DB_TYPE');
		$host = config('DB_HOST');
		$name = config('DB_NAME');
		$user = config('DB_USER');
		$pass = config('DB_PASS');
		$dsn = "{$type}:host={$host};dbname={$name};charset=utf8";
		T('poem_db_exec');
		$this->_conn  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
		$time = number_format(T('poem_db_exec',1)*1000,2);

		Log::trace('SQL',"PDO连接 [{$time}ms]");
	}

	function query($sql){
		T('poem_db_exec');
		if( is_null($this->_conn) ) $this->connect();
		$re = $this->_conn->query($sql);
		T('poem_db_exec',0);
		if( $re == false ) return null;
		return $re->fetchAll(\PDO::FETCH_ASSOC);
	}
	function select($sql,$bind){ return $this->exec($sql,$bind,'select'); }
	function insert($sql,$bind){ return $this->exec($sql,$bind,'insert'); }
	function update($sql,$bind){ return $this->exec($sql,$bind,'update'); }
	function delete($sql,$bind){ return $this->exec($sql,$bind,'delete'); }

	function exec($sql,$bind,$flag=''){
		if( is_null($this->_conn) ) $this->connect();

		T('poem_db_exec');
		$pre = $this->_conn->prepare($sql);
		foreach ($bind as $k => $v) $pre->bindValue($k,$v);
		$re  = $pre->execute();
		T('poem_db_exec',0);
		

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