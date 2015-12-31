<?php 
header("Content-Type: text/html;charset=utf-8");
header("X-Powered-By: PhpPoem_beta_v1.0");
defined('APP_DEBUG') || define('APP_DEBUG',false);     
define('APP_RUNTIME_PATH'  , APP_PATH.'Runtime/');     // 当前目录Framework为CF扩展目录
define('POEM_PATH'  , __DIR__.'/');     // 当前目录Framework为CF扩展目录
define('CORE_PATH'  , realpath(POEM_PATH.'Core').'/'); // Framework核心代码库
define('VENDOR_PATH', POEM_PATH.'Vendor/');  // 扩展包库
define('CORE_CONF'  , POEM_PATH.'config.php'); // Framework核心代码库
define('CORE_FUNC'  , POEM_PATH.'function.php'); // Framework核心代码库
define('APP_CONF'   , APP_PATH.'Common/config.php'); // 运行目录配置
define('APP_FUNC'   , APP_PATH.'Common/function.php');
define('APP_ROUTE'  , APP_PATH.'Common/route.php');
define('IS_AJAX'    , ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
define('IS_CLI'     , PHP_SAPI=='cli'? 1 : 0);
require CORE_PATH.'Poem.php';
\Poem\Poem::start();
?>