<?php 
namespace Poem\More;

class Build{
	protected static $m= '<?php
namespace [MODULE]\Model;
use Poem\Model;
class [MODEL]Model extends Model {

}';

	protected static $v = '<h3>{$varname}</h3>';

	protected static $c= '<?php
namespace [MODULE]\Controller;
use Poem\Controller;
class [CTRL] extends Controller {
    public function index(){
    	$info = \'Welcome to Use PhpPoem !\';

    	// 传递数据到view
    	assign(\'varname\', $info);

    	// 展示view  默认当前方法名即 App/[MODULE]/View/[CTRL]/index.html
    	v();
    }
}';
	static function checkModule($module){
		if( !is_dir(APP_PATH.$module) ){
			$ctrls  = explode(',', defined('NEW_CTRL')  ? NEW_CTRL : 'Index');
			$models = explode(',', defined('NEW_MODEL') ? NEW_MODEL : '' );
			self::initApp($module,$ctrls,$models);
		}
	}

	static function initApp($module,$ctrls=array(),$models=array()){
		if( !is_dir(APP_PATH) ){
			$re = mkdir(APP_PATH,0755,true);
			if( !$re ) \Poem\Poem::halt('应用目录创建失败：'.APP_PATH);
		}

		if( !is_writable(APP_PATH) )  \Poem\Poem::halt('应用目录不可写：'.APP_PATH);

		$cfg   = "<?php\nreturn array(\n\t//'key'=>'value'\n);\n";
		$route = "<?php\nreturn array(\n\t//'key'=>'value'\n);\n";

		$m_path = APP_PATH.$module.'/Model';
		$v_path = APP_PATH.$module.'/View';
		$c_path = APP_PATH.$module.'/Controller';

		$app = array(
			APP_PATH.'Common' => array(
				'config.php'  => $cfg,
				'function.php'=> '<?php ',
				'route.php'   => $route
				),
			APP_PATH.$module.'/Common' => array(
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