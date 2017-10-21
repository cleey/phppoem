<?php
namespace poem;

class app {
    static function start() {
        self::boot(); // 函数库

        require CORE_PATH . 'load.php';
        load::register(); // 自动加载，没有找到本地类的

        register_shutdown_function('\poem\app::appFatal'); // 错误和异常处理
        set_error_handler('\poem\app::appError');
        set_exception_handler('\poem\app::appException');

        t('POEM_TIME');

        $module = defined('NEW_MODULE') ? NEW_MODULE : 'home';
        if (!is_dir(APP_PATH . $module)) {
            \poem\more\Build::checkModule($module);
        }

        route::run(); // 路由管理
        self::exec(); // 执行操作

        t('POEM_TIME', 0);
        if (!config('debug_trace') || IS_AJAX || IS_CLI) {
            exit;
        }

        log::show();
    }

    // common
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

    // 加载配置
    static function exec() {
        t('POEM_EXEC_TIME');
        // 非法操作
        if (!preg_match('/^[A-Za-z](\w)*$/', POEM_FUNC)) {throw new \Exception('function: [' . htmlspecialchars(POEM_FUNC) . '] not exists');}

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
            $ctrl   = load::controller(POEM_CTRL); // 执行操作
            $method = new \ReflectionMethod($ctrl, POEM_FUNC);
            if ($method->isPublic()) {
                $method->invoke($ctrl);
            } else {
                throw new \ReflectionException();
            }

        } catch (\ReflectionException $e) {
            // 操作不存在
            if (method_exists($ctrl, '_empty')) {
                $method = new \ReflectionMethod($ctrl, '_empty');
                $method->invokeArgs($ctrl, [POEM_FUNC, '']);
            } else {
                throw new \Exception('method [ ' . (new \ReflectionClass($ctrl))->getName() . '->' . POEM_FUNC . ' ] not exists ', 10002);
            }
        }

        t('POEM_EXEC_TIME', 0);
    }

    // 接受PHP内部回调异常处理
    static function appException($e) {
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

        Log::push($err['message'], Log::ERR);
        self::halt($err);
    }

    // 自定义错误处理
    static function appError($errno, $errstr, $errfile, $errline) {
        $errStr = "$errstr $errfile 第 $errline 行.";
        Log::push($errStr, Log::ERR);

        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if (in_array($errno, $haltArr)) {
            self::halt($errStr);
        }

    }

    // 致命错误捕获
    static function appFatal() {
        $e       = error_get_last();
        $haltArr = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if ($e && in_array($e['type'], $haltArr)) {
            self::halt($e);
        }
    }

    // 错误输出
    static function halt($err) {
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

        if (IS_CLI) {
            exit(iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL . 'File: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
        }

        include CORE_PATH . 'tpl/exception.php';
        exit;
    }
}