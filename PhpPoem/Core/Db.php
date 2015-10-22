<?php
namespace Poem;

class Db{

	private static $_ins;
	protected $_conn = null;
	protected $_conn_cfg = array();

	static function getIns($config=array()){

		$key = md5(serialize($config));

		if( !(self::$_ins[$key] instanceof self) )
			self::$_ins[$key] = new self();
		self::$_ins[$key]->_conn_cfg = $config;
		return self::$_ins[$key];
	}

	private function connect(){
		$type = $this->_conn_cfg['DB_TYPE'];
		$host = $this->_conn_cfg['DB_HOST'];
		$port = $this->_conn_cfg['DB_PORT'];
		$name = $this->_conn_cfg['DB_NAME'];
		$user = $this->_conn_cfg['DB_USER'];
		$pass = $this->_conn_cfg['DB_PASS'];
		$char = $this->_conn_cfg['DB_CHARSET'];
		$dsn = "{$type}:host={$host};port={$port};dbname={$name};charset={$char}";
		T('poem_db_exec');
		$this->_conn  = new \PDO($dsn,$user,$pass) or die('数据库连接失败');
		$time = number_format(T('poem_db_exec',1)*1000,2);

		Log::trace('SQL',"PDO连接 [{$time}ms]");
	}

	function close(){
		$this->_conn = NULL;
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