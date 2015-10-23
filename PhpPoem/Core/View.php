<?php 
namespace Poem;

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
		$content = file_get_contents($tpl);
		// 开启页面布局
		if( ($layfile=config('LAYOUT')) && config('LAYOUT_ON') === true ){
			$layfile = $this->parseTpl($layfile);
			$content = str_replace('{__LAYOUT__}', $content, file_get_contents($layfile));
		}
		$content = $this->compiler($content); // 模板编译
		$content = $this->strip_whitespace($content); // 去掉空格什么的
		$filekey = md5($tpl); // 文件名
		$c_w_v_tpl = F($filekey,$content);
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
			$file = APP_PATH."{$module}/View/{$tpl}.html"; // html文件路径
		}elseif( strpos($tpl,':') !== false ){ // 指定文件夹 Index/index
			$tpl = str_replace(':', '/', $tpl);
			$file = APP_PATH.POEM_MODULE."/View/{$tpl}.html"; // html文件路径
		}else{
			$file = APP_PATH.POEM_MODULE."/View/".POEM_CTL."/{$tpl}.html"; // html文件路径
		}

		is_file($file) or \Poem\Poem::halt('文件不存在'.$file);

		return $file;
	}

	// 编辑文件
	protected function compiler($content){
		// 添加安全代码 代表入口文件进入的
        $content =  '<?php if (!defined(\'POEM_PATH\')) exit();?>'.$content;
        // 优化生成的php代码
        $content = str_replace('?><?php','',$content);

        // 匹配 {$vo['info']}
        $content = preg_replace_callback('/{\$([\w\[\]\'"]+)}/',
        	function($matches){return '<?php echo $'.$matches[1].';?>'; } ,
        	$content);

        // 匹配 <include file=""/>
        $content = preg_replace_callback(
        	'/<include[ ]*file=[\'"](.+)[\'"][ ]*\/>/',
        	function($matches){return '<?php include "'.$this->parseTpl($matches[1]).'"; ?>'; } ,
        	$content);
        
        // 匹配 <each key="" as=""></each>
		$content = preg_replace_callback(
			'/<each[ ]+key=[\'"](.+)[\'"][ ]*as=[\'"](.+)[\'"][ ]*>/',
			function($matches){return '<?php foreach( $'.$matches[1].' as $'.$matches[2].'){ ?>'; } ,
			$content);
		$content = str_replace('</each>', '<?php } ?>', $content);


		// 匹配 <if "$key == 1"></if>
		$content = preg_replace_callback(
			'/<if[ ]*[\'"](.+)[\'"][ ]*>/',
			function($matches){return '<?php if( $'.$matches[1].'){ ?>'; } ,
			$content);
		$content = str_replace('</if>', '<?php } ?>', $content);

        // CO($content);
        return $content;
	}

	// 页面跳转
	function autoJump($info,$url='',$second=false,$status=1){
		$key = $status == 1 ? 'message' : 'error';
		if( $url != '' ) $url = u($url);
		$url = $url ? $url : ($status == 1 ? $_SERVER["HTTP_REFERER"] : 'javascript:history.back(-1);');
		if( !$second ) $second = $status == 1 ? 1 : 3;
		$this->assign($key,$info);
		$this->assign('jumpUrl',$url);
		$this->assign('waitSecond',$second );

		$file = CORE_PATH.'Tpl/jump.php';
		
		$this->display($file);
		exit;
	}

	// 去除注释和空格
	function strip_whitespace($content) {
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


}

 ?>