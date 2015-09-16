<?php 


// 获取参数Get 和 Post
function I($param){
	return addslashes( trim( isset($_GET[$param]) ? $_GET[$param]: ( isset($_POST[$param]) ?$_POST[$param]:'' ) ) );
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
		else return $config[$name];
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
function Say($info){
	\Cleey\Log::push($info,'DEBUG');
}

// Model
function M($tb){
	$m = new \Cleey\Model($tb);
	return $m;
}



 ?>