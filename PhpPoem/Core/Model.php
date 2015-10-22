<?php
namespace Poem;

class Model{
	protected $_field = '*';
	protected $_where = array();
	protected $_limit = '';
	protected $_order = '';
	protected $_group = '';
	protected $_bind  = array();
	protected $_sql   = '';
	
	private $tb_name = '';
	function __construct($tb_name){
		$this->tb_name = $tb_name;
	}

	function _sql() {
		return $this->_sql;
	}


	function query($sql) {
		$this->_sql = $sql;
		$info = Db::getIns()->query($sql);
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

	function limit($b,$e){
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
		foreach ($data as $k => $v) {
			$keys .= "`$k`,";
			$vals .= ":$k,";
			$this->_bind[":$k"] = $v;
		}
		$keys = substr($keys, 0,-1);
		$vals = substr($vals, 0,-1);
		$this->_sql  = 'INSERT INTO '.$this->tb_name." ($keys) VALUES ($vals)";
		$info = Db::getIns()->insert($this->_sql,$this->_bind);
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
		$info = Db::getIns()->update($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function delete(){
		$this->_sql  = 'DELETE FROM '.$this->tb_name;
		$this->setWhere();
		$info = Db::getIns()->delete($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function select(){
		$this->_sql = 'SELECT '.$this->_field.' FROM `'.$this->tb_name.'`';
		$this->setWhere();
		$this->setGroup();
		$this->setOrder();
		$this->setLimit();
		$info = Db::getIns()->select($this->_sql,$this->_bind);
		$this->afterSql();
		return $info;
	}

	function count(){
		$this->_sql = 'SELECT count(*) as num FROM `'.$this->tb_name.'`';
		$this->setWhere();
		$this->setGroup();
		$this->setOrder();
		$this->setLimit();
		$info = Db::getIns()->select($this->_sql,$this->_bind);
		$this->afterSql();
		return $info[0]['num'];
	}

	function find(){
		$info = $this->select();
		return $info[0];
	}


	protected function afterSql(){
		foreach ($this->_bind  as $key => $value) {
			$this->_sql = str_replace($key, $value, $this->_sql);
		}
		$time = number_format(T('poem_db_exec',-1)*1000,2);
		Log::trace('SQL',$this->_sql."[{$time}ms]");
		$this->_where = array();
		$this->_limit = '';
		$this->_order = '';
		$this->_field = '*';
		$this->_bind  = array();
		// CO( $this->_sql );
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
				$keys[] = "`$k` ".$v[0]." :$k";
				if( strcasecmp($v[0],'IN')==0 && is_array($v[1]) ) $v[1] = implode(',', $v[1]);
				$bind[":$k"] = $v[1];
			}else{
				$keys[] = "`$k`=:$k";
				$bind[":$k"] = $v;
			}
		}
		$this->_sql .= ' WHERE '.implode($logic, $keys);
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