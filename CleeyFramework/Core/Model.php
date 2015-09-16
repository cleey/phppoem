<?php
namespace Cleey;

class Model{
	
	private $tb_name = '';
	function __construct($tb_name){
		$this->tb_name = $tb_name;
	}

	public function query($sql) {
		return Db::getIns()->query($sql);
	}

	public function select(){
		$sql = 'SELECT * FROM `'.$this->tb_name.'`';
		return Db::getIns()->query($sql);
	}
	
}



?>