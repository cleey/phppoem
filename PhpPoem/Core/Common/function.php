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
		$v = I($k[0]);
		if( $flag == 0 && $v==='' ) return gp_err($k);
		$params[ $k[0] ] = $v;
	}

	return count($params) == 1 ? current($params) : $params;
}

function gp_err($key){
	$flag = isset($key[1]) ? $key[1] :$key[0];
	$tmp = "{$flag} , 不能为空";
	if ( IS_AJAX ){ ajax(0,$tmp,'Parameter cannot be null'); }
	v_err($tmp);
	// return $info;
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
	// if( $flag == 0 ) \Poem\Poem::end();
	if( $flag == 0 ) \Poem\Poem::end();exit;
	// if( $flag == 2 ) \Poem\Poem::end();exit;
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
function l($info){
	\Poem\Log::push($info,'DEBUG');
}

// Model
function m($tb='',$config=''){
	static $model;
	if( !isset($model[$tb]) ){
		$class = 'Poem\\Model';
		if( is_file( $file = MODULE_MODEL.ucfirst($tb).'.php' ) ){
			include $file;
			$class = POEM_MODULE.'\\Model\\'.$tb;
		}
		$model[$tb] = new $class($tb,$config);
	}
	return $model[$tb];
}

// View
function v($tpl=''){
	$view = \Poem\Poem::instance('Poem\View');
	$view->display($tpl);
}
function assign($key,$value=''){
	$view = \Poem\Poem::instance('Poem\View');
	$view->assign($key,$value);
}
function v_ok($info,$url='',$second=false){
	$view = \Poem\Poem::instance('Poem\View');
	$view->autoJump($info,$url,$second,1);
}
function v_err($info,$url='',$second=false){
	$view = \Poem\Poem::instance('Poem\View');
	$view->autoJump($info,$url,$second,0);
}

// 文件缓存
function f($key='',$value='',$append=0){
	if( empty($key) ) return null;

	$obj = \Poem\Cache::getIns('File');
	if( $value === '') return $obj->get($key);
	else if( is_null($value) ) return $obj->del($key);
	else return $obj->set($key,$value,$append);
}

// 文件缓存
function s($key='',$value='',$options=null){
	$config = is_array($options) ? $options :null ; 
	if( is_null($key) ) \Poem\Cache::delIns();  // 删除实例

	$expire = is_numeric($options) ? $options : null;
	$obj = \Poem\Cache::getIns('',$config);
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
	if( !is_file($file) ){\Poem\Poem::halt('文件不存在: '.$file);}
	$_file[$require_class] = true;
	require $file;
}

function p($m,$url='',$page_size=15,$show_nums=5){
	$page = intval( I('p')) ? intval( I('p')) : 1;
	$tm  = clone $m;
	$total = $tm->count(); // 总记录数
	$list = $m->limit( ($page-1)*$page_size ,$page_size )->select();  // 结果列表
	$info['total'] = $total; // 总记录数
	$info['np'] = $page;  // 当前页
	$info['tp'] = ceil((int)$info['total']/(int)$page_size);  //总页数
	$info['url'] = $url;  //url

	$info['list'] = $list;
	$info['page'] = $page;
	$info['html'] = pagehtml($page,$info['tp'],$url,$show_nums);
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
		$html .= '<ul class="pagination">';
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
			$cp = ($np == $i) ? 'class="active"':''; //'.$cp.'
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
        'prefix'    =>  config('COOKIE_PREFIX'), // cookie 名称前缀
        'expire'    =>  config('COOKIE_EXPIRE'), // cookie 保存时间
        'path'      =>  config('COOKIE_PATH'), // cookie 保存路径
        'domain'    =>  config('COOKIE_DOMAIN'), // cookie 有效域名
        'secure'    =>  config('COOKIE_SECURE'), //  cookie 启用安全传输
        'httponly'  =>  config('COOKIE_HTTPONLY'), // httponly设置
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
				$name = config('SESSION_PREFIX').$name;
				list($k1,$k2) = explode('.',$name);
				return isset($_SESSION[$k1][$k2]) ? $_SESSION[$k1][$k2] : NULL;
			}else return $_SESSION[$name];
		}
	}elseif( is_null($value) ){
		unset($_SESSION[$value]);
	}else{ // 设置 $name
		$name = config('SESSION_PREFIX').$name;
		if( strpos($name, '.') ){
			list($k1,$k2) = explode('.',$name);
			$_SESSION[$k1][$k2] = $value;
		}else $_SESSION[$name] = $value;
	}
}

function layout($flag){
	if( $flag !== false ){
		config('LAYOUT_ON',true);
		if( is_string($flag) ) config('LAYOUT',$flag);
	}else config('LAYOUT_ON',false);
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
	header("Location: $url");
	exit;
}

function u($tpl){
	if( strpos($tpl, '//') !== false ) return $tpl;
	// list($module,$class,$func) = explode('\\', get_class($this) );
	$tpl = $tpl != '' ? $tpl : POEM_FUNC;

	if( strpos($tpl,'@') !== false ){ // 模块 Home@Index/index
		list($module,$tpl) = explode('@', $tpl );
		$url = POEM_URL.'/'.$module.'/'.$tpl; // html文件路径
	}elseif( strpos($tpl,':') !== false ){ // 指定文件夹 Index/index
		$tpl = str_replace(':', '/', $tpl);
		$url = POEM_MODULE_URL.'/'.$tpl; // html文件路径
	}else{
		$url = POEM_CTL_URL.'/'.$tpl; // html文件路径
	}

	return $url;
}

?>
