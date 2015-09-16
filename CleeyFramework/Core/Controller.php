<?php 
namespace Cleey;
class Controller{

	protected $html_vars = array(); // 用户assign 变量

	// 展示页面
	function display($tpl=''){
		echo $this->fetch($tpl);
	}
	// 执行页面并返回执行结果
	function fetch($tpl=''){
		// 模板文件
		$c_w_v_tpl = $this->parseTpl($tpl);
		// 模板变量
		extract($this->html_vars);

		// 缓冲区
		ob_start();
		ob_implicit_flush(0);
		include $c_w_v_tpl;

		// 获取并清空缓存
    	return ob_get_clean();
	}

	// 用户变量
	function assign($key,$value=''){
		if( is_array($key) )
			$this->html_vars = array_merge($this->html_vars,$key);
		else
			$this->html_vars[$key] = $value;
	}

	// 返回成功跳转
	function success($info,$url='',$second=false){
		$this->autoJump($info,$url,$second,1);
	}

	// 返回失败跳转
	function error($info,$url='',$second=false){
		$this->autoJump($info,$url,$second,0);
	}

	protected function parseTpl($tpl=''){
		if( is_file($tpl) ) return $tpl;

		// list($module,$class,$func) = explode('\\', get_class($this) );
		$tpl = $tpl != '' ? $tpl : CF_METHOD;

		if( strpos($tpl,'@') !== false ){ // 模块 Home@Index/index
			list($module,$tpl) = explode('@', $tpl );
			$file = APP_PATH."{$module}/View/{$tpl}.html"; // html文件路径
		}elseif( strpos($tpl,'/') !== false ){ // 指定文件夹 Index/index
			$file = APP_PATH.CF_MODULE."/View/{$tpl}html"; // html文件路径
		}else{
			$file = APP_PATH.CF_MODULE."/View/".CF_CLASS."/{$tpl}.html"; // html文件路径
		}

		is_file($file) or die('文件不存在'.$file);

		return $file;
	}

	// 页面跳转
	protected function autoJump($info,$url='',$second=false,$status=1){
		$key = $status == 1 ? 'message' : 'error';
		$url = $url ? $url : ($status == 1 ? $_SERVER["HTTP_REFERER"] : 'javascript:history.back(-1);');
		if( !$second ) $second = $status == 1 ? 1 : 3;
		$this->assign($key,$info);
		$this->assign('jumpUrl',$url);
		$this->assign('waitSecond',$second );

		$file = CORE_PATH.'Tpl/jump.php';
		
		$this->display($file);
		exit;
	}

}

 ?>