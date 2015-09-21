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
		$where['id'] = 12;
		$id = M('more')->where($where)->delete();
		CO($id);
		return;
		$dsn = "mysql:host=localhost;dbname=test;charset=utf8";
		$db = new \PDO($dsn,'root','') or die('数据库连接失败');
		# the data we want to insert
		$data= array('1', 'Cardiff');
		 
		$re = $db->query("show columns from more");
		$re = $re->fetchAll();
		CO($re,1);

		$re = $db->prepare("INSERT INTO more (addr, NaMe) values (?, ?)");
		$id = $re->execute($data);
		// $re = $db->prepare("INSERT INTO more (id, NaMe) values (?, ?)");
		// $id = $re->execute($data);
		echo $db->lastInsertId();

		// $this->success('hello , im test');
		// $this->error('hello , im test');
		// $this->success('hello , im test');
		
	}


}
?>