<?php 
header("Content-Type: text/html;charset=utf-8");
header("X-Powered-By: PhpPoem_beta_v1.0");
defined('APP_DEBUG') || define('APP_DEBUG',false);     
define('APP_RUNTIME_PATH'  , APP_PATH.'runtime/');     // 运行时临时文件目录
define('POEM_PATH'  , __DIR__.'/');     // phppoem目录
define('CORE_PATH'  , realpath(POEM_PATH.'core').'/'); // Framework核心代码库
define('VENDOR_PATH', POEM_PATH.'vendor/');  // 扩展包库
define('CORE_CONF'  , POEM_PATH.'config.php'); // Framework核心代码库
define('CORE_FUNC'  , POEM_PATH.'function.php'); // Framework核心代码库
define('APP_CONF'   , APP_PATH.'config.php'); // 运行目录配置
define('APP_FUNC'   , APP_PATH.'function.php');
define('APP_ROUTE'  , APP_PATH.'route.php');
define('IS_AJAX'    , ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
define('IS_CLI'     , PHP_SAPI=='cli'? 1 : 0);
require CORE_PATH.'poem.php';
\poem\poem::start();
?>