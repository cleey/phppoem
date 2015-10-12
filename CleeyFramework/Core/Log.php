<?php 
namespace Cleey;

class Log{
	const ERR    = 'ERR';
	const WARN   = 'WARN';
	const NOTICE = 'NOTICE';
	const INFO   = 'INFO';
	const DEBUG  = 'DEBUG';

	private static $info = array();  // 日志信息
	private static $trace = array();  // 日志信息
	static function push($info,$lvl=self::DEBUG){
		array_push(self::$info,"{$lvl}: {$info}");
		self::trace($lvl,$info);
	}

	// 请求结束由框架保存
	static function down(){
		echo '<br>=============== LOG ===============<br>';
		foreach (self::$info as $v) echo '-- ',$v,'<br>';
	}

	static function trace($key,$value){
		if( !C('DEBUG_TRACE') ) return;
		self::$trace[$key][] = $value;
	}

	// 请求结束由框架保存
	static function show(){
		if( !C('DEBUG_TRACE') ) return; 
		$trace_tmp = self::$trace;
		$files  =  get_included_files();
		foreach ($files as $key=>$file){
            $files[$key] = $file.' ( '.number_format(filesize($file)/1024,2).' KB )';
        }
        $time = \Cleey\Cleey::$time;
        $trace_tmp['SYS'] = array(
			"总吞吐量" => number_format(1/$time['CLEEY_TIME'],2).' req/s' ,
        	"总共时间" => $time['CLEEY_TIME'].' s' ,
			"框架加载" => ($time['CLEEY_TIME'] - $time['APP_TIME']).' s' ,
			"App时间"  => $time['APP_TIME'].' s' ,
			"内存使用" => (memory_get_usage()/1024/1024).' MB' ,
			'文件加载' =>count($files),
			'会话信息' => 'SESSION_ID='.session_id()
		);

		$trace_tmp['FILE'] = $files;

		$arr = array(
				'SYS' => '基本',
				'FILE' => '文件',
				'ERR' => '错误',
				'SQL' => '数据库',
				'DEBUG' => '调试',
			);
		foreach ($arr as $key => $value) {
			$trace[$value] = $trace_tmp[$key];
		}
		include CORE_PATH.'Tpl/trace.php';
	}
}


 ?>
