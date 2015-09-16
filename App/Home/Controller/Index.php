<?php 
namespace Home\Controller;
use Cleey\Controller;

class Index extends Controller{

	function index(){

		echo I('i');
		// return;
		// $re = M('user')->select();
		// $re = M('user')->select();
		CO($re,1);

		// \Cleey\Log::push('test','DEBUG');
		// Say('test');
		echo "<br>wo cao hello world<br>";
		// CO( class_exists('Redis') );

		$this->assign('list',array('helo','wold'));
		$this->display();
	}

	function test(){
		
		$this->success('hello , im test');
		$this->error('hello , im test');
		// $this->success('hello , im test');
		
	}


}
?>