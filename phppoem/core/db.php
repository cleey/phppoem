<?php
namespace poem;

class db{

	private static $_ins = array();
	public  $_conn = null;
	protected $_conn_cfg;

	static function getIns($config){
		$key = md5( is_array($config)?serialize($config):$config );
		if( !isset(self::$_ins[$key]) || !(self::$_ins[$key] instanceof self) ){
			self::$_ins[$key] = new self();
			self::$_ins[$key]->_conn_cfg = $config;
			self::$_ins[$key]->connect();
		}
		return self::$_ins[$key];
	}

	private function connect(){
		if( is_array($this->_conn_cfg) ){
			$type = $this->_conn_cfg['db_type'];
			$host = $this->_conn_cfg['db_host'];
			$port = $this->_conn_cfg['db_port'];
			$name = $this->_conn_cfg['db_name'];
			$user = $this->_conn_cfg['db_user'];
			$pass = $this->_conn_cfg['db_pass'];
			$char = $this->_conn_cfg['db_charset'];
			$dsn = "{$type}:host={$host};port={$port};dbname={$name};charset={$char}";
		}else{
			$dsn = $this->_conn_cfg;
		}
		T('poem_db_exec');
		$this->_conn  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
		$time = number_format(T('poem_db_exec',1)*1000,2);

		Log::trace('SQL',"PDO连接 [{$time}ms]");
	}

	function close(){
		$this->_conn = NULL;
	}

	function query($sql){
		if( is_null($this->_conn) ) $this->connect();
		T('poem_db_exec');
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

	static function clear(){
		if(empty(self::$_ins)) return;
		foreach (self::$_ins as &$single)
			$single->close();
	}
}

?>