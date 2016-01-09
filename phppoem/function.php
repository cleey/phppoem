<?php 

// 获取参数Get 和 Post
function i($param){
	return htmlspecialchars( trim( isset($_GET[$param]) ? $_GET[$param]: ( isset($_POST[$param]) ?$_POST[$param]:'' ) ) );
}

// 获取参数Get 和 Post
function gp($param,$flag = 0){
	$arr = explode(',', $param);
	// 分解 | key和val
	foreach ($arr as $value) {
		$k = explode('|',$value);
		$v = i($k[0]);
		if( $flag == 0 && $v==='' ){
			$flag = isset($key[1]) ? $key[1] :$key[0];
			$tmp = "{$flag} , 不能为空";
			if ( IS_AJAX ){ ajax(0,$tmp,'Parameter cannot be null'); }
			err_jump($tmp);
		}
		$params[ $k[0] ] = $v;
	}
	return count($params) == 1 ? current($params) : $params;
}

// 读取和加载配置文件
function config($name=null,$value=null){
	static $config = array();
	if( empty($name) ) return $config;
	if( is_string($name) ){
		if( !is_null($value) ) $config[$name] = $value;
		else return isset($config[$name]) ? $config[$name] : null;
	}
	if( is_array($name) ) $config = array_merge($config,$name);
	return null;
}

// 输出
function co($var,$flag=0){
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
	$flag == 0 && exit;
}

// 返回ajax
function ajax($code,$info='',$more='',$upd_url=0){
	$re['code'] = $code;
	$re['info'] = $info;
	$re['more'] = $more;
	$re['upd_url'] = $upd_url;
	if ( IS_AJAX ){
		echo json_encode($re);
		exit;
	}else{
		CO( $re );
	}
}

// 日志
function l($info){ \poem\log::push($info,'DEBUG'); }
// 异常退出
function e($info){ throw new Exception($info, 1); }

// Model
function m($tb='',$config=''){
	static $model;
	if( !isset($model[$tb]) ){
		$class = 'poem\\model';
		if( is_file( $file = MODULE_MODEL.strtolower($tb).'.php' ) ){
			include $file;
			$class = POEM_MODULE.'\\model\\'.$tb;
		}
		$model[$tb] = new $class($tb,$config);
	}
	return $model[$tb];
}

// View
function v($tpl=''){\poem\load::instance('poem\view')->display($tpl);}
function fetch($tpl=''){return \poem\load::instance('poem\view')->fetch($tpl);}
function assign($key,$value=''){\poem\load::instance('poem\view')->assign($key,$value);}
function ok_jump($info,$url='',$param='',$second=false){\poem\load::instance('poem\view')->autoJump($info,$url,$param,$second,1);}
function err_jump($info,$url='',$param='',$second=false){\poem\load::instance('poem\view')->autoJump($info,$url,$param,$second,0);}

// 文件缓存 append 0覆盖  1追加 2检查
function f($key='',$value='',$append=0){
	if( empty($key) ) return null;

	$obj = \poem\cache::getIns('file');
	if( $append == 2 ) return $obj->has($key);
	if( $value === '') return $obj->get($key);
	else if( is_null($value) ) return $obj->del($key);
	else return $obj->set($key,$value,$append);
}

// 缓存
function s($key='',$value='',$options=null){
	$config = is_array($options) ? $options :null ; 
	if( is_null($key) ) \poem\cache::close();  // 删除实例

	$expire = is_numeric($options) ? $options : null;
	$obj = \poem\cache::getIns('',$config);
	if( $key === '' ){ return $obj->_ins; } // 返回实例

	if( $value === '') return $obj->get($key);
	else if( is_null($value) ) return $obj->del($key);
	else return $obj->set($key,$value,$expire);
}

// 扩展包
function vendor($require_class,$ext='.php'){
	static $_file = array();
	if( class_exists($require_class) ) return true;
	if( isset($_file[$require_class]) ) return true;
	$file = VENDOR_PATH.$require_class.$ext;
	if( !is_file($file) ){\poem\app::halt('文件不存在: '.$file);}
	$_file[$require_class] = true;
	require $file;
}

// cookie
function cookie($name='',$value='',$option=null){
	if( empty($name) ) return $_COOKIE;
	$cfg = array(
        'prefix'    =>  config('cookie_prefix'), // cookie 名称前缀
        'expire'    =>  config('cookie_expire'), // cookie 保存时间
        'path'      =>  config('cookie_path'), // cookie 保存路径
        'domain'    =>  config('cookie_domain'), // cookie 有效域名
        'secure'    =>  config('cookie_secure'), //  cookie 启用安全传输
        'httponly'  =>  config('cookie_httponly'), // httponly设置
    );
	$name = $cfg['prefix'].$name;
	if( $value === '') return $_COOKIE[$name];

    if( !is_null($option) ){
    	if(is_numeric($option)) $cfg['expire'] = $option;
    	else if( is_string($option) ){
    		parse_str($option,$option);
    		$cfg = array_merge($cfg,$option);
    	}
    }

	if( is_null($value) ) {
		$cfg['expire'] = time()-3600;
		unset($_COOKIE[$name]);
	}
	setcookie($name,$value,$cfg['expire'],$cfg['path'],$cfg['domain'],$cfg['secure'],$cfg['httponly']);
}

// session的使用
function session($name='',$value=''){
	if( $value===''){
		if( $name === '') return $_SESSION ;
		else if( strpos($name, '[') === 0 ){
			switch ($name) {
				case '[pause]': session_write_close(); break;
				case '[start]': session_start(); break;
				case '[destroy]': session_unset();session_destroy(); break;
				case '[regenerate]': session_regenerate_id(); break;
				default: break;
			}
		}elseif( is_null($name) ){
			unset($_SESSION);
		}else{
			if( strpos($name, '.') ){
				$name = config('session_prefix').$name;
				list($k1,$k2) = explode('.',$name);
				return isset($_SESSION[$k1][$k2]) ? $_SESSION[$k1][$k2] : NULL;
			}else return $_SESSION[$name];
		}
	}elseif( is_null($value) ){
		unset($_SESSION[$name]);
	}else{ // 设置 $name
		$name = config('session_prefix').$name;
		if( strpos($name, '.') ){
			list($k1,$k2) = explode('.',$name);
			$_SESSION[$k1][$k2] = $value;
		}else $_SESSION[$name] = $value;
	}
}

function layout($flag){
	if( $flag !== false ){
		config('layout_on',true);
		if( is_string($flag) ) config('layout',$flag);
	}else config('layout_on',false);
}

// 计时函数
function t($key,$end='',$settime=null){
	static $time = array(); // 计时
	if( empty($key) ) return $time;
	if( !is_null($settime) ){
		$time[$key] = $settime;
		return ;
	}
	if( $end === -1 )return  $time[$key];  // 返回key
	else if( $end === 1 ) return  microtime(1)-$time[$key];  // 返回上次key到这次结果
	else if( $end === 0 ) $time[$key] = microtime(1)-$time[$key]; // 记录上次key到这次时间
	else if( !empty($end) ){
		if( !isset($time[$end]) ) $time[$end] = microtime(1);
		return $time[$end]-$time[$key];
	}else $time[$key] = microtime(1);
}

function jump($url){
	poem_url($url);
	header("Location: $url");
	exit;
}

function poem_url($url){
	if( strpos($url, '//') !== false )return $url;
	if( strpos($url, '/')  === 0 )return $url;
	$module= strtolower(POEM_MODULE);
	$class = strtolower(POEM_CTRL);
	$func  = POEM_FUNC;
	$tmp = explode('/', trim($url,'/') );
	switch(count($tmp)){
		case 1: $func = $tmp[0];break;
		case 2: $class = $tmp[0];$func = $tmp[1];break;
		case 3: $module = $tmp[0];$class = $tmp[1];$func = $tmp[2];break;
	}
	return POEM_URL."/$module/$class/$func"; // html文件路径
}

?>
