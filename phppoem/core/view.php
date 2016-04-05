<?php 
namespace poem;

class View{

	protected $html_vars = array(); // 用户assign 变量

	function display($tpl=''){
		echo $this->fetch($tpl);
	}

	// 用户变量
	function assign($key,$value=''){
		if( is_array($key) )
			$this->html_vars = array_merge($this->html_vars,$key);
		else
			$this->html_vars[$key] = $value;
	}

	function fetch($tpl=''){
		// 模板文件
		T('POEM_COMPILE_TIME');
		$tpl     = $this->parseTpl($tpl);

		$filekey = str_replace(APP_PATH, '', $tpl); // 文件名 home/index/index.html
		$c_w_v_tpl = f($filekey,'',2);  // 判断是否存在
		if( APP_DEBUG || $c_w_v_tpl === false ){
			$content = file_get_contents($tpl);
			// 开启页面布局
			if( ($layfile=config('layout')) && config('layout_on') === true ){
				$layfile = $this->parseTpl($layfile);

				$content = str_replace('{__LAYOUT__}', $content, file_get_contents($layfile));
			}
			$content = $this->compiler($content); // 模板编译
			$c_w_v_tpl = f($filekey,$content,-1);
			// F($filekey, php_strip_whitespace($c_w_v_tpl) ); // 去掉空格什么的
		}
		T('POEM_COMPILE_TIME',0);

		// 模板变量
		if( !empty($this->html_vars) ) extract($this->html_vars);
		$this->html_vars = array(); // 清空
		// 缓冲区
		ob_start();
		ob_implicit_flush(0);
		include $c_w_v_tpl;

		// 获取并清空缓存
    	return ob_get_clean();
	}

	// 获取指定页面文件绝对路径
	protected function parseTpl($tpl=''){
		if( is_file($tpl) ) return $tpl;

		// list($module,$class,$func) = explode('\\', get_class($this) );
		$tpl = $tpl != '' ? $tpl : POEM_FUNC;

		if( strpos($tpl,'@') !== false ){ // 模块 Home@Index/index
			list($module,$tpl) = explode('@', $tpl );
			$file = APP_PATH."{$module}/view/{$tpl}.html"; // html文件路径
		}elseif( strpos($tpl,':') !== false ){ // 指定文件夹 Index/index
			$tpl = str_replace(':', '/', $tpl);
			$file = APP_PATH.POEM_MODULE."/view/{$tpl}.html"; // html文件路径
		}else{
			$file = APP_PATH.POEM_MODULE."/view/".POEM_CTRL."/{$tpl}.html"; // html文件路径
		}

		is_file($file) or \poem\app::halt('文件不存在'.$file);

		return $file;
	}

	// 编辑文件
	protected function compiler($content){
		// 添加安全代码 代表入口文件进入的
        $content =  '<?php if (!defined(\'POEM_PATH\')) exit();?>'.$content;
        $content = preg_replace(
        	array(
				'/{\$([\w\[\]\'"\$]+)}/s', // 匹配 {$vo['info']}
				'/{\:([^\}]+)}/s', // 匹配 {:func($vo['info'])}
				'/<each[ ]+[\'"](.+)[\'"][ ]*>/', // 匹配 <each "$list as $v"></each>
				'/<if[ ]*[\'"](.+)[\'"][ ]*>/', // 匹配 <if "$key == 1"></if>
	        	'/<elseif[ ]*[\'"](.+)[\'"][ ]*>/'
        	),
        	array(
        		'<?php echo $\\1;?>',
        		'<?php echo \\1;?>',
        		'<?php foreach( \\1 ){ ?>',
        		'<?php if( \\1 ){ ?>',
        		'<?php }elseif( \\1 ){ ?>'
        	),
        	$content);
		$content = str_replace(array('</if>','<else />','</each>','POEM_URL','POEM_MODULE_URL','POEM_CTRL_URL','POEM_FUNC_URL'), array('<?php } ?>','<?php }else{ ?>','<?php } ?>',POEM_URL,POEM_MODULE_URL,POEM_CTRL_URL,POEM_FUNC_URL), $content);
        // 匹配 <include "Public:menu"/>
        $content = preg_replace_callback(
        	'/<include[ ]+[\'"](.+)[\'"][ ]*\/>/',
        	function($matches){return $this->compiler(file_get_contents( $this->parseTpl($matches[1]) )); } ,
        	$content);

        return $content;
	}

	protected function compile_include($content){
		// 匹配 <include file=""/>
        $flag = preg_match_all('/<include\sfile=[\'"](.+)[\'"]\s\/>/',$content,$matches);
        foreach ($matches[1] as $v) {
        	$tmp = $this->compiler(file_get_contents( $this->parseTpl($matches[1]) ));
        	preg_replace('/<include\sfile=[\'"]'.$v.'[\'"]\s\/>/', $tmp, $content);
        }
        return $content;
	}

	// 页面跳转
	function autoJump($info,$url='',$param='',$second=false,$status=1){
		$key = $status == 1 ? 'message' : 'error';
		if( $url != '' ) $url = poem_url($url);
		if( $param ) $url .= $param;
		$url = $url ? $url : ($status == 1 ? $_SERVER["HTTP_REFERER"] : 'javascript:history.back(-1);');
		if( !$second ) $second = $status == 1 ? 1 : 3;
		$this->assign($key,$info);
		$this->assign('jumpUrl',$url);
		$this->assign('waitSecond',$second );

		$file = CORE_PATH.'tpl/jump.php';
		
		$this->display($file);
		exit;
	}


}

 ?>