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
		if( $flag == 0 && $v==='' ) return gp_err($k);
		$params[ $k[0] ] = $v;
	}

	return count($params) == 1 ? current($params) : $params;
}

function gp_err($key){
	$flag = isset($key[1]) ? $key[1] :$key[0];
	$tmp = "{$flag} , 不能为空";
	if ( IS_AJAX ){ ajax(0,$tmp,'Parameter cannot be null'); }
	err_jump($tmp);
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
	switch ($flag) {
		case -1: exit; break;
		case 0: \Poem\Poem::end(); exit; break;
		case 1: break;
		default: break;
	}
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

// 异常退出
function e($info){
	throw new Exception($info, 1);
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
function ok_jump($info,$url='',$param='',$second=false){
	$view = \Poem\Poem::instance('Poem\View');
	$view->autoJump($info,$url,$param,$second,1);
}
function err_jump($info,$url='',$param='',$second=false){
	$view = \Poem\Poem::instance('Poem\View');
	$view->autoJump($info,$url,$param,$second,0);
}

// 文件缓存 append 0覆盖  1追加 2检查
function f($key='',$value='',$append=0){
	if( empty($key) ) return null;

	$obj = \Poem\Cache::getIns('File');
	if( $append == 2 ) return $obj->has($key);
	if( $value === '') return $obj->get($key);
	else if( is_null($value) ) return $obj->del($key);
	else return $obj->set($key,$value,$append);
}

// 缓存
function s($key='',$value='',$options=null){
	$config = is_array($options) ? $options :null ; 
	if( is_null($key) ) \Poem\Cache::close();  // 删除实例

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

function p($m,$url='',$affix='',$page_size=15,$show_nums=5){
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
	$info['html'] = pagehtml($page,$info['tp'],$affix,$url,$show_nums);
	return $info;
}

function pagehtml($np,$tp,$affix,$url,$num=5){
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
			$html .= "<li class='{$f}'><a href='$url/p/1$affix'> << </a></li>";
			$html .= "<li class='{$f}'><a href='$url/p/$up$affix'> < </a></li>";
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
			$tu = ($np == $i) ? 'javascript:void(0);' : $url."/p/$i$affix";
			$html .= "<li $cp><a href='$tu'>$i</a></li>";
		}
		if($np != $tp){
			$html .= "<li class='{$e}'><a href='{$url}/p/{$dp}{$affix}'> > </a></li>";
			$html .= "<li class='{$e}'><a href='{$url}/p/{$tp}{$affix}'> >> </a></li>";
		}
		$html .= "</ul>";
	}
	return $html;
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

function u($tpl){
	if( strpos($tpl, '//') !== false ) return $tpl;
	$tpl = $tpl != '' ? $tpl : POEM_FUNC;

	if( strpos($tpl,'@') !== false ){ // 模块 Home@Index/index
		list($module,$tpl) = explode('@', $tpl );
		$url = POEM_URL.'/'.$module.'/'.$tpl; // html文件路径
	}elseif( strpos($tpl,':') !== false ){ // 指定文件夹 Index/index
		$tpl = str_replace(':', '/', $tpl);
		$url = POEM_MODULE_URL.'/'.$tpl; // html文件路径
	}else{
		$url = POEM_CTRL_URL.'/'.$tpl; // html文件路径
	}

	return $url;
}

function poem_url($url){
	if( strpos($url, '//') !== false )return $url;
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


/**
 * @cc 上传文件函数
 * @param  [type] $data url         [存储地址]
 * @param  [type] $data size        [限制大小]
 * @param  [type] $data allowedExts [允许后缀]
 * @return [type]              [返回数组] info url ，code 1 成功 0失败，filename文件名
 */
function fileUpload($data){
	
	foreach ($data as $k => $v) {
		if( empty($v) ){ return array('code'=> 0,'info'=> '参数不能为空：'.$k); }
	}
	if( !is_dir($data['url']) ){ return array('code'=> 0,'info'=> '路径错误：'.$data['url']); }
	$fileField = $data['fileField']?: 'file';
	$file = $_FILES[$fileField];

	$ext = pathinfo($file["name"],PATHINFO_EXTENSION);
	// 文件过大
	if ( $file["size"] > $data['size'] ){
		$return = array('code'=> 0,'info'=> '文件过大：'.$file["size"].'，请上传小于：'.$data['size']);
	}
	// 不允许后缀
	elseif( !in_array($ext, $data['allow']) ){
		$return = array('code'=> 0,'info'=> '不允许后缀：'.$ext.'请上传：'.implode(',',$data['allow']) );
	}else{
		if ($file["error"] > 0){
			$return =array('code'=> 0,'info'=> "Return Code: " . $file["error"]);
		}
		else{
			if( !$data['filename'] ){ 
				// $data['filename'] = Date('YmdHis').'_'.$file["name"];
				$data['filename'] = date('YmdHis').'_'.uniqid().'.'.$ext;
			}
			$newfile_url = $data['url'].$data['filename'];
			move_uploaded_file($file["tmp_name"],$newfile_url);
			$return = array(
				'code'   => 1,
				'origin' => $_FILES[$fileField]["name"],
				'size'   => $_FILES[$fileField]["size"],
				'name'   => $data['filename'],
				'type'   => $ext,
				'info'   => '/'.$newfile_url);
		}
	}
	return $return;
}


// 去除注释和空格 优化php
function self_php_strip_whitespace($content) {
	$stripStr   = '';
	//分析php源码
	$tokens     = token_get_all($content);
	$last_space = false;
	for ($i = 0, $j = count($tokens); $i < $j; $i++) {
		if (is_string($tokens[$i])) {
			$last_space = false;
			$stripStr  .= $tokens[$i];
		} else {
			switch ($tokens[$i][0]) {
				//过滤各种PHP注释
				case T_COMMENT:
				case T_DOC_COMMENT: break;
				//过滤空格
				case T_WHITESPACE:
					if (!$last_space) {
						$stripStr  .= ' ';
						$last_space = true;
					}
					break;
				case T_START_HEREDOC:
					$stripStr .= "<<<Poem\n";
					break;
				case T_END_HEREDOC:
					$stripStr .= "Poem;\n";
					for($k = $i+1; $k < $j; $k++) {
						if(is_string($tokens[$k]) && $tokens[$k] == ';') {
							$i = $k;
							break;
						} else if($tokens[$k][0] == T_CLOSE_TAG) {
							break;
						}
					}
					break;
				default:
					$last_space = false;
					$stripStr  .= $tokens[$i][1];
			}
		}
	}
	return $stripStr;
}


?>
