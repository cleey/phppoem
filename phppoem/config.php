<?php
return array(
    'layout_on'       => true,
    'layout'          => false,

    // 数据库配置
    'db_type'         => 'mysql',
    'db_host'         => 'localhost',
    'db_name'         => '',
    'db_user'         => '',
    'db_pass'         => '',
    'db_prefix'       => '',
    'db_port'         => '3306',
    'db_charset'      => 'utf8',
    'db_dsn'          => '',
    'db_deploy'       => false, // 部署方式: false 集中式(单一服务器),true 分布式(主从服务器)
    'db_rw_separate'  => false, // 数据库读写是否分离 主从式有效
    'db_master_num'   => 1, // 读写分离后 主服务器数量
    'db_slave_no'     => '', // 指定从服务器序号

    'runtime_path'    => APP_PATH . '/runtime/', // 运行目录
    
    'session_type'    => '',
    'session_prefix'  => '',
    'default_module'  => 'home', // 默认模块
    'default_errcode' => 1, // 默认ajax 错误码，gp()使用

    'storage_type'  => 'file',
    'storage_expire'=> 86400, // 1day
    'storage_compress'=> false, // 缓存压缩

    'cookie_expire'   => 0,
    'cookie_domain'   => '',
    'cookie_path'     => '/',
    'cookie_prefix'   => '',
    'cookie_secure'   => false, // cookie 启用安全传输 true 在https下会传输，http不会传输
    'cookie_httponly' => false, // true 无法通过程序读取如 JS脚本、Applet等

    'log_path'        => '', // 日志路径
    'log_level'       => 5,
    'log_remain_days' => 1, // 日志保留天数
    'log_relative_path' => true, // 相对路径日志还是全路径日志

    'debug_trace'     => false, // 页面右下角展示调试信息
);