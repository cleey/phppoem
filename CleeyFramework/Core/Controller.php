<?php 
namespace Cleey;
class Controller{

	protected $view; // view 类
	
	function __construct(){
		$this->view = Cleey::instance('Cleey\View');
	}
	// 展示页面
	function display($tpl=''){
		$this->view->display($tpl);
	}
	// 执行页面并返回执行结果
	function fetch($tpl=''){
		$this->view->fetch($tpl);
	}

	// 用户变量
	function assign($key,$value=''){
		$this->view->assign($key,$value);
	}

	// 返回成功跳转
	function success($info,$url='',$second=false){
		$this->view->autoJump($info,$url,$second,1);
	}

	// 返回失败跳转
	function error($info,$url='',$second=false){
		$this->view->autoJump($info,$url,$second,0);
	}

	

	

}

 ?>