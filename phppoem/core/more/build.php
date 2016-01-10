<?php 
namespace poem\more;
class build{
	protected static $m= '<?php
namespace [MODULE]\model;
use poem\model;
class [MODEL] extends model {
}';
	protected static $v = '<h3>{$varname}</h3>';
	protected static $c= '<?php
namespace [MODULE]\controller;
class [CTRL]{
    public function index(){
    	echo \'Welcome to use PhpPoem !\';
    }
    public function viewtest(){
    	$info = \'Welcome to Use Phppoem !\';
    	
    	assign(\'varname\', $info);// 传递数据到view
    	// 展示view  默认当前方法名视图
    	// app/模块/view/控制器/方法.html 即
    	// app/home/view/index/viewtest.html
    	v();
    }
}';
	static function checkModule($module){
		if( !is_dir(APP_PATH.$module) ){
			$ctrls  = defined('NEW_CTRL')  ? explode(',', NEW_CTRL)  : array('index');
			$models = defined('NEW_MODEL') ? explode(',', NEW_MODEL) : array();
			self::initApp(strtolower($module),$ctrls,$models);
		}
	}
	static function initApp($module,$ctrls=array(),$models=array()){
		if( !is_dir(APP_PATH) ){
			$re = mkdir(APP_PATH,0755,true);
			if( !$re ) \poem\poem::halt('应用目录创建失败：'.APP_PATH);
		}
		if( !is_writable(APP_PATH) )  \poem\poem::halt('应用目录不可写：'.APP_PATH);
		$cfg   = "<?php\nreturn array(\n\t//'key'=>'value'\n);\n";
		$route = "<?php\nreturn array(\n\t//'key'=>'value'\n);\n";
		$m_path = APP_PATH.$module.'/model';
		$v_path = APP_PATH.$module.'/view';
		$c_path = APP_PATH.$module.'/controller';
		$app = array(
			trim(APP_PATH,'/') => array(
				'config.php'  => $cfg,
				'function.php'=> '<?php ',
				'route.php'   => $route
				),
			// APP_PATH.$module.'/boot' => array(
			// 	'config.php'  => $cfg,
			// 	'function.php'=> '<?php '
			// 	),
			$v_path => array(),
			$c_path => array(),
		);
		if( !empty($models) ) $app[] = $m_path => array();
		
		foreach ($app as $dir => $v) {
			if( !is_dir($dir) ) mkdir($dir,0755,true);
			foreach ($v as $file => $data)
				if( !is_file("$dir/$file") ) file_put_contents("$dir/$file", $data);
		}
		foreach ($ctrls as $ctrl) {
			$ctrl = strtolower($ctrl);
			mkdir("$v_path/$ctrl",0755,true);
			$html_fn = "$v_path/$ctrl/viewtest.html";
			!is_file($html_fn) && file_put_contents($html_fn, self::$v);
			$data = str_replace(array('[MODULE]','[CTRL]'), array($module,$ctrl), self::$c);
			$ctrl_fn = "$c_path/$ctrl.php";
			!is_file($ctrl_fn) && file_put_contents($ctrl_fn, $data);
		}
		foreach ($models as $model) {
			$model = strtolower($model);
			$data = str_replace(array('[MODULE]','[MODEL]'), array($module,$model), self::$m);
			$model_fn = "$m_path/$model.php";
			!is_file($model_fn) && file_put_contents($model_fn, self::$m);
		}
	}
}
?>