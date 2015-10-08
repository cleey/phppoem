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
	if( is_null($name) ) return $config;
	if( empty($name) ) return null;
	if( is_string($name) ){
		if( $value != null ) $config[$name] = $value;
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

function SafePage($m,$url='',$listnum=15){
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
	return $info;
}


 ?>