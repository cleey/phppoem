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
    	$info = \'Welcome to Use Phppoem !\';

    	// 传递数据到view
    	assign(\'varname\', $info);

    	// 展示view  默认当前方法名视图即 app/[MODULE]/view/index/index.html
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
			if( !$re ) \poem\app::halt('应用目录创建失败：'.APP_PATH);
		}

		if( !is_writable(APP_PATH) )  \poem\app::halt('应用目录不可写：'.APP_PATH);

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
			APP_PATH.$module.'/common' => array(
				'config.php'  => $cfg,
				'function.php'=> '<?php '
				),
			$m_path => array(),
			$v_path => array(),
			$c_path => array(),
		);
		foreach ($app as $dir => $v) {
			if( !is_dir($dir) ) mkdir($dir,0755,true);
			foreach ($v as $file => $data)
				if( !is_file("$dir/$file") ) file_put_contents("$dir/$file", $data);
		}

		foreach ($ctrls as $ctrl) {
			mkdir("$v_path/$ctrl",0755,true);
			file_put_contents("$v_path/$ctrl/index.html", self::$v);
			$data = str_replace(array('[MODULE]','[CTRL]'), array($module,$ctrl), self::$c);
			file_put_contents("$c_path/$ctrl.php", $data);
		}
		foreach ($models as $model) {
			$data = str_replace(array('[MODULE]','[MODEL]'), array($module,$model), self::$m);
			file_put_contents("$m_path/$model.php", self::$m);
		}
	}


}
?>