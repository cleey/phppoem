<?php 
namespace poem\session;

class Db extends \SessionHandler{
	protected $maxtime= '';
	protected $table  = '';

	public function open($savePath, $session_id) { 
		if( config('session_expire') ) ini_set('session.gc_maxlifetime',config('session_expire'));
		$this->maxtime = ini_get('session.gc_maxlifetime');
		$this->table   = config('session_table')?:"session";
		return true; 
	} 

	public function close() {
		$this->gc($this->maxtime); 
		return true; 
	}	 

	public function read($session_id) { 
		$re = m($this->table)->field('session_data')->where(['session_id'=>$session_id,'session_expire'=>['>',time()] ])->find();
		return $re['session_data'];
	}

	public function write($session_id, $session_data) { 
	   	$map  = array( 'session_id' => $session_id );
	   	$data = array(
	   		'session_data'   => $session_data,
	   		'session_expire' => time() + $this->maxtime,
	   	);
	   	if( m($this->table)->where($map)->find() ){
	   		$re = m($this->table)->where($map)->update($data);
	   	}else{
	   		$re = m($this->table)->insert(array_merge($map,$data));
	   	}
		return $re ? true : false;
	} 

	public function destroy($session_id) { 
		$re = m($this->table)->where(['session_id'=>$session_id])->delete();
		return $re ? true : false;
	} 

	public function gc($sessMaxLifeTime) {
		$re = m($this->table)->where(['session_expire'=>['<',time()] ])->delete();
		return $re ? true : false;
	} 

}
/*
数据库方式Session驱动
CREATE TABLE session (
	session_id varchar(255) NOT NULL,
	session_expire int(11) NOT NULL,
	session_data blob,
	UNIQUE KEY `session_id` (`session_id`)
);
*/