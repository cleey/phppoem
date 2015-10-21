<?php 
namespace Poem;

class Route{

	static function run(){
		$url = array();
		if( isset($_SERVER['PATH_INFO']) ){
			$_URL = $_SERVER['PATH_INFO'];
			$_EXT = pathinfo($_URL,PATHINFO_EXTENSION);  // 获取url后缀
			if( $_EXT ) $_URL = preg_replace('/\.'.$_EXT.'$/i', '', $_URL); // 删除url后缀
			$_URL = self::parseRule($_URL);
			$url = explode('/', $_URL); // /Home/Index/index
		}
		// CO($_SERVER);
		define('POEM_MODULE' , !empty($url[1]) ? ucfirst($url[1]) : 'Home');
		define('POEM_CTL'  , !empty($url[2]) ? ucfirst($url[2]) : 'Index');
		define('POEM_FUNC' , !empty($url[3]) ? $url[3] : 'index');
		if( isset($url[4]) ) self::parseParam(array_slice($url, 4));

		define('POEM_URL' , $_SERVER['SCRIPT_NAME']); // 项目入口文件 */index.php
		define('POEM_ROOT' , dirname(POEM_URL));  // 顶级web目录
		define('POEM_MODULE_URL', POEM_URL.'/'.POEM_MODULE);  // class url
		define('POEM_CTL_URL'   , POEM_URL.'/'.POEM_MODULE.'/'.POEM_CTL);  // class url
		define('POEM_FUNC_URL'  , POEM_URL.'/'.POEM_MODULE.'/'.POEM_CTL.'/'.POEM_FUNC);  // method url
	}

	private static function parseRule($url){
		$rule = include APP_ROUTE;
		foreach ($rule as $pattern => $path) {
			preg_match_all('/{(\w+)}/', $pattern, $matchs,PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
			$tmp = '';
			$pos = 0;
			$vars= array();
			foreach ($matchs as $match) {
				$tmp .= preg_quote(substr($pattern,$pos,$match[0][1]-$pos),'/').'(\w+)';
				$pos = $match[0][1]+strlen($match[0][0]); // offset + var_len
				$vars[] = $match[1][0]; // varname
			}
			$tmp .= preg_quote(substr($pattern, $pos),'/');
			$flag = preg_match_all('#^'.$tmp.'$#', $url, $values); // 匹配url
			if( $flag ){
				foreach ($vars as $k => $name) $_GET[$name] = $values[$k+1][0];
				$url = $path;
				break;
			}
		}
		return $url;
	}

	private static function parseParam($param){
		$len = floor( count($param)/2 );
		$i = 0;
		while($len--){ $_GET[$param[$i]] = $param[$i+1]; $i+=2; }
	}

}


?>