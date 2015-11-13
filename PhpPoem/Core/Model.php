<?php
namespace Poem;

class Model{
	protected $_db = null; // 数据库资源
	protected $db_cfg = array(); // 数据库配置

	protected $_field = '*';
	protected $_where = array();
	protected $_limit = '';
	protected $_order = '';
	protected $_group = '';
	protected $_bind  = array();
	protected $_sql   = '';
	
	private $tb_name = '';
	function __construct($tb_name='',$config=''){
		$this->tb_name = $tb_name;
		if( $config === '' ){
			// 配置文件
			$this->db_cfg = array(
				'db_type' => config('db_type'),
				'db_host' => config('db_host'),
				'db_port' => config('db_port'),
				'db_name' => config('db_name'),
				'db_user' => config('db_user'),
				'db_pass' => config('db_pass'),
				'db_charset' => config('db_charset'),
			);
		}else if( is_array($config)  ){
			// 用户指定配置
			$this->db_cfg = $config;
		}else if( is_string($config) ){
			// db dsn配置
			$tmp = parse_url($config);
			$this->db_cfg = array(
				'db_type' => isset($tmp['scheme']) ?$tmp['scheme'] : config('db_type'),
				'db_host' => isset($tmp['host']) ? $tmp['host'] : config('db_host'),
				'db_port' => isset($tmp['port']) ? $tmp['port'] : config('db_port'),
				'db_name' => isset($tmp['path']) ? substr($tmp['path'],1) : config('db_name'),
				'db_user' => isset($tmp['user']) ? $tmp['user'] : config('db_user'),
				'db_pass' => isset($tmp['pass']) ? $tmp['pass'] : config('db_pass'),
				'db_charset' => isset($tmp['fragment']) ? $tmp['fragment'] :config('db_charset')
			);
		}
	}

	function db(){
		if( $this->_db !== null ) return $this->_db;
		$this->_db = Db::getIns($this->db_cfg);
		return $this->_db;
	}

	function sql() {
		return $this->_sql;
	}


	function query($sql) {
		$this->_sql = $sql;
		$info = $this->db()->query($sql);
		$this->afterSql();
		return $info;
	}

	function field($str){
		$this->_field = $str;
		return $this;
	}

	function where($arr){
		$this->_where = array_merge($this->_where,$arr);
		return $this;
	}

	function limit($b=0,$e=0){
		if( $e == 0 ){ $e=$b; $b=0;}
		$this->_limit = $b;
		if( $e ) $this->_limit .= ",$e";
		return $this;
	}

	function order($str){
		$this->_order = $str;
		return $this;
	}
	function group($str){
		$this->_group = $str;
		return $this;
	}

	function insert($data=null){
		if( $data == null ){ return; }
		// INSERT INTO more (id, NaMe) values (?, ?)
		$keys = '';$vals='';
		foreach ($data as $k => $v) {
			if(is_null($v)) continue;
			$keys .= "`$k`,";
			$vals .= ":$k,";
			$this->_bind[":$k"] = $v;
		}
		$keys = substr($keys, 0,-1);
		$vals = substr($vals, 0,-1);
		$this->_sql  = 'INSERT INTO '.$this->tb_name." ($keys) VALUES ($vals)";
		$info = $this->db()->insert($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function update($data=null){
		if( $data == null ){ return; }
		if( isset($data['id']) ){
			$this->where(array('id'=>$data['id']));
			unset($data['id']);
		}
		if( empty($this->_where) ) return false;
		foreach ($data as $k => $v) {
			$keys .= "`$k`=:$k,";
			$bind[":$k"] = $v;
		}
		$keys = substr($keys, 0,-1);
		$this->_bind = array_merge($this->_bind,$bind);

		$this->_sql  = 'UPDATE '.$this->tb_name." SET {$keys}";
		$this->setWhere();
		$info = $this->db()->update($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function delete(){
		$this->_sql  = 'DELETE FROM '.$this->tb_name;
		$this->setWhere();
		$info = $this->db()->delete($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function select(){
		$this->_sql = 'SELECT '.$this->_field.' FROM `'.$this->tb_name.'`';
		$this->setWhere();
		$this->setGroup();
		$this->setOrder();
		$this->setLimit();
		$info = $this->db()->select($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function count(){
		$this->_sql = 'SELECT count(*) as num FROM `'.$this->tb_name.'`';
		$this->setWhere();
		$this->setGroup();
		$this->setOrder();
		$this->setLimit();
		$info = $this->db()->select($this->_sql,$this->_bind);
		$this->afterSql();
		return $info[0]['num'];
	}

	function find(){
		$info = $this->select();
		return $info[0];
	}

	function inc($column){
		if( $column == null ){ return; }
		if( empty($this->_where) ) return false;
		$this->_sql  = 'UPDATE '.$this->tb_name." SET `{$column}`=`{$column}`+1";
		$this->setWhere();
		$info = $this->db()->update($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function id($id){
		return $this->where(array('id'=>$id))->find();
	}

	protected function afterSql(){
		foreach ($this->_bind  as $key => $value) {
			$this->_sql = str_replace($key, substr($value, 0,10), $this->_sql);
		}
		$time = number_format(T('poem_db_exec',-1)*1000,2);
		Log::trace('SQL',$this->_sql."[{$time}ms]");
		$this->_where = array();
		$this->_limit = '';
		$this->_order = '';
		$this->_field = '*';
		$this->_bind  = array();
	}

	protected function setWhere(){
		if( empty($this->_where) ) return false;
		$str = '';
		$logic = 'AND';
		if( isset( $this->_where['_logic'] ) ){
			$logic = $this->_where['_logic'];
			unset($this->_where['_logic']);
		}
		foreach ($this->_where as $k => $v) {
			if( is_array($v) ){
				$keys[] = "`$k` ".$v[0]." :w_$k";
				if( strcasecmp($v[0],'IN')==0 && is_array($v[1]) ) $v[1] = implode(',', $v[1]);
				$bind[":w_$k"] = $v[1];
			}else{
				$keys[] = "`$k`=:w_$k";
				$bind[":w_$k"] = $v;
			}
		}
		$this->_sql .= ' WHERE '.implode(" $logic ", $keys);
		$this->_bind = array_merge($this->_bind,$bind);
	}

	protected function setOrder(){
		if( empty($this->_order) ) return false;
		$this->_sql .= ' ORDER BY '.$this->_order;
	}

	protected function setLimit(){
		if( empty($this->_limit) ) return false;
		$this->_sql .= ' LIMIT '.$this->_limit;
	}

	protected function setGroup(){
		if( empty($this->_group) ) return false;
		$this->_sql .= ' GROUP BY '.$this->_group;
	}
}



?>