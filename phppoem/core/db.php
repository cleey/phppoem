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
			$tmp = explode('@',$this->_conn_cfg);
			if( count($tmp) == 2 ){
				list($user,$pass) = explode(':',$tmp[0]);
				$dsn = $tmp[1];
			}else{
				$dsn = $this->_conn_cfg;
			}
		}
		T('poem_db_exec');
		$char = $char ? $char : 'utf8';
		$this->_conn  = new \PDO($dsn,$user,$pass, array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES '$char'")) or die('数据库连接失败');
		$time = number_format(T('poem_db_exec',1)*1000,2);

		Log::trace('SQL',"PDO连接 [{$time}ms]");
	}

	function close(){
		$this->_conn = NULL;
	}
	function beginTransaction(){
		$this->_conn->beginTransaction();
	}
	function rollBack(){
		$this->_conn->rollBack();
	}
	function commit(){
		$this->_conn->commit();
	}

	function query($sql){
		if( is_null($this->_conn) ) $this->connect();
		T('poem_db_exec');
		try{
			$re = $this->_conn->query($sql);
			if(!$re){ throw new \Exception(implode(', ', $pre->errorInfo() ) ); }
			T('poem_db_exec',0);
			if( $re == false ) return null;
			return $re->fetchAll(\PDO::FETCH_ASSOC);
		}catch(\PDOException $e){
			throw new \Exception(implode(', ', $e->errorInfo));
		}
	}
	function execute($sql){
		if( is_null($this->_conn) ) $this->connect();
		T('poem_db_exec');
		try{
			$re = $this->_conn->exec($sql);
			T('poem_db_exec',0);
			return $re;
		}catch(\PDOException $e){
			throw new \Exception(implode(', ', $e->errorInfo));
		}
	}
	function select($sql,$bind){ return $this->exec($sql,$bind,'select'); }
	function insert($sql,$bind){ return $this->exec($sql,$bind,'insert'); }
	function update($sql,$bind){ return $this->exec($sql,$bind,'update'); }
	function delete($sql,$bind){ return $this->exec($sql,$bind,'delete'); }

	function exec($sql,$bind,$flag=''){
		if( is_null($this->_conn) ) $this->connect();
		T('poem_db_exec');
		try{
			$pre = $this->_conn->prepare($sql);
			if( !$pre ) throw new \Exception(implode($this->_conn->errorInfo()) );
			foreach ($bind as $k => $v) $pre->bindValue($k,$v);
			$re = $pre->execute();
			if(!$re){ throw new \Exception(implode(', ', $pre->errorInfo() ) ); }

			T('poem_db_exec',0);
			switch ($flag) {
				case 'insert': return $this->_conn->lastInsertId(); break;
				case 'update': return $pre->rowCount(); break;
				case 'delete': return $pre->rowCount(); break;
				case 'select': return $pre->fetchAll(\PDO::FETCH_ASSOC); break;
				default: break;
			}
		}catch(\PDOException $e){
			throw new \Exception(implode(', ', $e->errorInfo));
		}
	}

	static function clear(){
		if(empty(self::$_ins)) return;
		foreach (self::$_ins as &$single)
			$single->close();
	}
}

?>