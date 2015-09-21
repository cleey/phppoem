<?php
namespace Cleey;

class Model{
	protected $_where = array();
	protected $_limit = '';
	protected $_order = '';
	protected $_bind  = array();
	protected $_sql   = '';
	
	private $tb_name = '';
	function __construct($tb_name){
		$this->tb_name = $tb_name;
	}

	function query($sql) {
		return Db::getIns()->query($sql);
	}

	function where($arr){
		$this->_where = array_merge($this->_where,$arr);
		return $this;
	}

	function limit($b,$e){
		$this->_limit = $b;
		if( $e ) $this->_limit .= ",$e";
		return $this;
	}

	function order($str){
		$this->_order = $str;
		return $this;
	}

	function insert($data=null){
		if( $data == null ){ return; }
		// INSERT INTO more (id, NaMe) values (?, ?)
		foreach ($data as $k => $v) {
			$keys .= "`$k`,";
			$vals .= ":$k,";
			$bind[":$k"] = $v;
		}
		$keys = substr($keys, 0,-1);
		$vals = substr($vals, 0,-1);
		$this->_sql  = 'INSERT INTO '.$this->tb_name." ($keys) VALUES ($vals)";
		return Db::getIns()->insert($this->_sql,$bind);
	}

	function update($data=null){
		if( $data == null ){ return; }
		foreach ($data as $k => $v) {
			$keys .= "`$k`=:$k,";
			$bind[":$k"] = $v;
		}
		$keys = substr($keys, 0,-1);
		$this->_bind = array_merge($this->_bind,$bind);

		$this->_sql  = 'UPDATE '.$this->tb_name." SET {$keys}";
		$this->setWhere();
		// CO($this->_sql,1);
		// CO($this->_bind);
		return Db::getIns()->update($this->_sql,$this->_bind);
	}

	function delete(){
		$this->_sql  = 'DELETE FROM '.$this->tb_name;
		$this->setWhere();
		return Db::getIns()->delete($this->_sql,$this->_bind);
	}

	function select(){
		$sql = 'SELECT * FROM `'.$this->tb_name.'`';
		$this->setWhere();
		$this->setOrder();
		$this->setLimit();
		return Db::getIns()->select($this->_sql);
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
			$keys[] = "`$k`=:$k";
			$bind[":$k"] = $v;
		}
		$this->_sql .= ' WHERE '.implode($logic, $keys);
		$this->_bind = array_merge($this->_bind,$bind);
	}

	protected function setOrder(){
		if( empty($this->_limit) ) return false;
		$this->_sql .= ' ORDER BY '.$this->_order;
	}

	protected function setLimit(){
		if( empty($this->_limit) ) return false;
		$this->_sql .= ' LIMIT '.$this->_limit;
	}
}



?>