<?php 
namespace poem\session;
class Redis extends \SessionHandler{
	protected $maxtime= '';
	protected $table  = '';

	public function open ($savePath, $session_id) { 
		$this->maxtime = ini_get('session.gc_maxlifetime');
		$this->table   = config('session_table')?:"session";
		return true; 
	} 

	public function close (){
		return true;
	}

	public function read ($session_id){
		return redis($this->table.$session_id)['session_data'];
	}

	public function write ($session_id, $session_data){
		return redis($this->table.$session_id,$session_data,$this->maxtime);
	} 

	public function destroy ($session_id){
		return redis($this->table.$session_id,null);
	} 

	public function gc ($sessMaxLifeTime){
		return true;
	} 
}