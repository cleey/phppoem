<?php
namespace poem;

class app {
    /**
     * poem框架启动入口
     * @return null
     */
    static function start() {
        // 公共函数库，公共文件
        self::boot();

        // 注册自动加载
        require CORE_PATH . 'load.php';
        load::register();

        // 错误和异常处理
        register_shutdown_function('\poem\app::app_fatal');
        set_error_handler('\poem\app::app_error');
        set_exception_handler('\poem\app::app_exception');

        t('POEM_TIME'); // 计时

        $module = defined('NEW_MODULE') ? NEW_MODULE : 'home';
        if (!is_dir(APP_PATH . $module)) {
            \poem\more\build::checkModule($module);
        }

        route::run(); // 路由管理

        t('POEM_EXEC_TIME');
        self::exec(); // 执行操作

        self::end();
    }

    /**
     * 结束统计时间，以及展示日志等
     * @return void
     */
    static function end($show_log=true){
        log::get_instance()->clean_log();

        t('POEM_EXEC_TIME', 0);
        t('POEM_TIME', 0); // 计时结束
        if ($show_log && config('debug_trace') && !IS_AJAX && !IS_CLI) {
            log::show();
        }
        exit;
    }

    /**
     * 加载公共函数,配置
     * @return null
     */
    static function boot() {
        // 加载方法
        $time = microtime(1);
        include CORE_FUNC; // 核心库
        if (is_file(APP_FUNC)) {
            include APP_FUNC;
        }
        // App公共
        t('POEM_FUNC_TIME', '', microtime(1) - $time);

        // 加载配置
        t('POEM_CONF_TIME');
        config(include CORE_CONF); // 核心库
        if (is_file(APP_CONF)) {
            config(include APP_CONF);
        }
        // App公共
        t('POEM_CONF_TIME', 0);
    }

    /**
     * 执行用户代码
     * @return null
     */
    static function exec() {
        // 非法操作
        if (!preg_match('/^[A-Za-z](\w)*$/', POEM_FUNC)) {
            $e =new \Exception('function: [' . htmlspecialchars(POEM_FUNC) . '] not exists');
            self::app_exception($e, false);
        }

        if (is_file($file = APP_PATH . POEM_MODULE . '/boot/function.php')) {
            include $file;
        }
        // 请求模块
        if (is_file($file = APP_PATH . POEM_MODULE . '/boot/config.php')) {
            config(include $file);
        }
        // 请求模块
        // load::instance(POEM_MODULE.'\\controller\\'.POEM_CTRL, POEM_FUNC);
        try {
            $ctrl = load::controller(POEM_CTRL); // 执行操作
            $method = new \reflectionMethod($ctrl, POEM_FUNC);
            if ($method->isPublic()) {
                $method->invoke($ctrl);
            } else {
                throw new \reflectionException('module('.POEM_MODULE.') controller('.POEM_CTRL.') func('.POEM_FUNC.') not found');
            }

        } catch (\ReflectionException $e) {
            // 操作不存在
            if (function_exists('_app_empty_call')) {
                _app_empty_call($e);
            } else {
                // 不存在的ctrl/func抛出异常, 不写日志
                self::app_exception($e, false);
            }
        }
    }

    /**
     * 异常Exception处理
     * @param class $e Exception
     * @param bool $is_write_log 是否写日志，默认写
     * @return null
     */
    static function app_exception($e, $is_write_log = true) {
        $err            = array();
        $err['message'] = $e->getMessage();
        $trace          = $e->getTrace();
        if ('E' == $trace[0]['function']) {
            $err['file'] = $trace[0]['file'];
            $err['line'] = $trace[0]['line'];
        } else {
            $err['file'] = $e->getFile();
            $err['line'] = $e->getLine();
        }
        $err['trace'] = $e->getTraceAsString();

        self::halt($err, $is_write_log);
    }

    /**
     * 自定义错误处理
     * @param  int $errno 错误代码
     * @param  string $errstr 错误信息
     * @param  string $errfile 错误文件
     * @param  int $errline 文件错误行号
     * @return null
     */
    static function app_error($errno, $errstr, $errfile, $errline) {
        $errStr = "$errstr $errfile 第 $errline 行.";

        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if (in_array($errno, $haltArr)) {
            self::halt($errStr);
        }
    }

    /**
     * 致命错误Fatal捕获
     * @return null
     */
    static function app_fatal() {
        $e       = error_get_last();
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if ($e && in_array($e['type'], $haltArr)) {
            self::halt($e);
        }
    }

    /**
     * 异常处理并结束
     * @param array/string $err 异常信息
     * @param bool $is_write_log 是否写日志，默认写
     * @return null
     */
    static function halt($err, $is_write_log = true) {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            if (!is_array($err)) {
                $trace        = debug_backtrace();
                $e['message'] = $err;
                $e['file']    = $trace[0]['file'];
                $e['line']    = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $err;
            }

        } else {
            $err          = is_array($err) ? $err['message'] : $err;
            $e['message'] = config('sys_error_msg') ?: $err;
        }

        $is_write_log && l("${e['file']}:${e['line']} ${e['message']}", log::FATAL, 2);
        
        $log_id = log::get_instance()->get_log_id();
        if (IS_CLI || IS_AJAX) {
            $log_str = iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL .
                'File: ' . $e['file'] . ':' . $e['line'] . PHP_EOL .
                'LogID:'. $log_id . PHP_EOL .
                $e['trace'];
            exit($log_str);
        }

        include CORE_PATH . 'tpl/exception.php';
        exit;
    }
}
