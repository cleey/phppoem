<?php 
header("Content-Type: text/html;charset=utf-8");
header("X-Powered-By: CleeyFramework_beta_v1.0");

define('CLEEY_PATH' , __DIR__.'/');     // 当前目录CleeyFramework为CF扩展目录
define('CORE_PATH'  , realpath(CLEEY_PATH.'Core').'/'); // CleeyFramework核心代码库
define('VENDOR_PATH'   , CLEEY_PATH.'Vendor/');  // 扩展包库

define('CORE_CONF'  , CORE_PATH.'Common/config.php'); // CleeyFramework核心代码库
define('CORE_FUNC'  , CORE_PATH.'Common/function.php'); // CleeyFramework核心代码库

// 运行目录配置
define('APP_CONF'   , APP_PATH.'Common/config.php');
define('APP_FUNC'   , APP_PATH.'Common/function.php');
define('APP_CACHE'   , APP_PATH.'Cache/');
// AJAX 请求
define('IS_AJAX'    , ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);

require CORE_PATH.'Cleey.php';
Cleey\Cleey::start();

?>