<?php 


// 获取参数Get 和 Post
function I($param){
	return htmlspecialchars( trim( isset($_GET[$param]) ? $_GET[$param]: ( isset($_POST[$param]) ?$_POST[$param]:'' ) ) );
}

// 获取参数Get 和 Post
function GP($param,$flag = 0){
	$arr  = explode(',', $param);

	// 分解 | key和val
	foreach ($arr as $value) {
		$k = explode('|',$value);
		$v = I($k[0]);
		if( $flag == 0 && $v==='' ) return ParamError($k);
		$params[ $k[0] ] = $v;
	}

	return count($params) == 1 ? current($params) : $params;
}

function ParamError($key){
	$info = array();
	$info['CP'] = true;
	$info['key'] = $key[0];
	if( isset($key[1]) ) $flag = $key[1];
	$tmp = "{$flag} , 不能为空";
	if ( IS_AJAX ){ CA(0,$tmp,'Parameter cannot be null'); }
	$info['msg'] = $tmp;
	$info['val'] = $flag;
	// $this->error($tmp) ;exit;
	return $info;
}

// 读取和加载配置文件
function C($name=null,$value=null){
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
function CO($var,$flag=0){
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
	if( $flag == 0 ) exit;
}

// 返回ajax
function CA($code,$info='',$more='',$upd_url=0){
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
function L($info){
	\Cleey\Log::push($info,'DEBUG');
}

// Model
function M($tb){
	static $model;
	if( !isset($model[$tb]) ) $model[$tb] = new \Cleey\Model($tb);
	return $model[$tb];
}

// Model
function F($key,$value=null,$append=0){
	$key = APP_CACHE.$key.'.php';
	if( !is_dir(APP_CACHE) ) mkdir(APP_CACHE);
	if( $value === null) return \Cleey\Cache::get($key);
	else{
		\Cleey\Cache::set($key,$value,$append);
		return $key;
	}
}

// 扩展包
function Vendor($require_class,$ext='.php'){
	static $_file = array();
	if( class_exists($require_class) ) return true;
	if( isset($_file[$require_class]) ) return true;
	$file = VENDOR_PATH.$require_class.$ext;
	if( !is_file($file) ){\Cleey\Cleey::halt('文件不存在: '.$file);}
	$_file[$require_class] = true;
	require $file;
}

function P($m,$url='',$listnum=15){
	$page = intval( I('p')) ? intval( I('p')) : 1;
	$tm  = clone $m;
	$total = $tm->count(); // 总记录数
	$list = $m->limit( ($page-1)*$listnum ,$listnum )->select();  // 结果列表
	$info['total'] = $total; // 总记录数
	$info['np'] = $page;  // 当前页
	$info['tp'] = ceil((int)$info['total']/(int)$listnum);  //总页数
	$info['url'] = $url;  //url

	$info['list'] = $list;
	$info['page'] = $page;
	$info['html'] = pageHtml($page,$info['tp'],$url);
	return $info;
}

function pageHtml($np,$tp,$url,$num=5){
// $np = 4;
// $tp = 10;
// header('Content-Type:text/html;charset=utf-8');
	$np	 = (int)$np;   // 当前页
	$tp  = (int)$tp;   // 总页数
	$up	 = $np-1;   // 上一页
	$dp  = $np+1;   // 下一页
	$f 	 = ($np == 1)?'disabled':'';   // 是否为首页
	$e 	 = ($np == $tp)?'disabled':'';  // 是否问尾页
	$html = '';
	if( $tp > 0){
		$html .= '<ul class="pagination fr">';
		// $html .= "<li> <span>共 $total 条 </span> </li>";
		// $html .= "<li> <span>当前 $np / $tp 页</span> </li>";
		if($np !=1){
			$html .= "<li class='{$f}'><a href='$url?p=1&&$clin_page_str'> << </a></li>";
			$html .= "<li class='{$f}'><a href='$url?p=$up&&$clin_page_str'> < </a></li>";
		}
		$sep = floor($num/2);
		$begin = 1;
		if( $tp >= $num ){
			if($np > $sep && $np < ($tp - $sep) ){ $begin = $np - $sep;}
			else if($np >= ($tp - $sep) ){ $begin = $tp - $num + 1; }
		}else{
			$num = $tp;
		}
		$sum = 0;
		for ($i=$begin; $i < $num+$begin; $i++) { 
			$cp = ($np == $i) ? 'class="disabled"':''; //'.$cp.'
			$tu = ($np == $i) ? 'javascript:void(0);' : $url."?p=$i&&$clin_page_str";
			$html .= "<li $cp><a href='$tu'>$i</a></li>";
		}
		if($np != $tp){
			$html .= "<li class='{$e}'><a href='{$url}?p={$dp}&&{$clin_page_str}'> > </a></li>";
			$html .= "<li class='{$e}'><a href='{$url}?p={$tp}&&{$clin_page_str}'> >> </a></li>";
		}
		$html .= "</ul>";
	}
	return $html;
}
// cookie
function cookie($name='',$value='',$option=null){
	if( empty($name) ) return $_COOKIE;
	$cfg = array(
        'prefix'    =>  C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire'    =>  C('COOKIE_EXPIRE'), // cookie 保存时间
        'path'      =>  C('COOKIE_PATH'), // cookie 保存路径
        'domain'    =>  C('COOKIE_DOMAIN'), // cookie 有效域名
        'secure'    =>  C('COOKIE_SECURE'), //  cookie 启用安全传输
        'httponly'  =>  C('COOKIE_HTTPONLY'), // httponly设置
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
				$name = C('SESSION_PREFIX').$name;
				list($k1,$k2) = explode('.',$name);
				return isset($_SESSION[$k1][$k2]) ? $_SESSION[$k1][$k2] : NULL;
			}else return $_SESSION[$name];
		}
	}elseif( is_null($value) ){
		unset($_SESSION[$value]);
	}else{ // 设置 $name
		$name = C('SESSION_PREFIX').$name;
		if( strpos($name, '.') ){
			list($k1,$k2) = explode('.',$name);
			$_SESSION[$k1][$k2] = $value;
		}else $_SESSION[$name] = $value;
	}
}

function layout($flag){
	if( $flag !== false ){
		C('LAYOUT_ON',true);
		if( is_string($flag) ) C('LAYOUT',$flag);
	}else C('LAYOUT_ON',false);
}

// 计时函数
function T($key,$end=''){
	static $time = array(); // 计时
	if( empty($key) ) return;
	if( $end === 1 && isset($time[$key]) ) return  microtime(1)-$time[$key];
	if( !empty($end) ) return  $time[$end]-$time[$key];
	$time[$key] = microtime(1);
}

?>
