<?php 
namespace Cleey;

class Log{
	const ERR    = 'ERR';
	const WARN   = 'WARN';
	const NOTICE = 'NOTICE';
	const INFO   = 'INFO';
	const DEBUG  = 'DEBUG';

	private static $info = array();  // 日志信息
	static function push($info,$lvl=self::DEBUG){
		array_push(self::$info,"{$lvl}: {$info}");
	}

	// 请求结束由框架保存
	static function down(){
		echo '<br>=============== LOG ===============<br>';
		foreach (self::$info as $v) echo '-- ',$v,'<br>';
	}

}


 ?>
