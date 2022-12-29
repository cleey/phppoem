<?php
namespace poem;
class log {
    const FATAL = 1;
    const ERR   = 2;
    const WARN  = 3;
    const INFO  = 4;
    const DEBUG = 5;
    const DEPTH_FILTER_POEM = -1; // 过滤poem_path

    private $levels = array(
        self::FATAL => 'FATAL',
        self::ERR => 'ERR',
        self::WARN => 'WARN',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
    );

    private static $instance;

    protected $log_level;
    protected $log_remain_days;
    protected $log_id;
    protected $log_dir;
    protected $log_file;
    private $log_relative_path; // 相对地址则需要清理项目路径前缀
    private $project_path;

    protected $log_switch = true; // 日志开关

    private static $trace = array(); // 页面展示日志信息

    /**
     * 构造文件
     * @param array $cfg log_* 配置
     */
    function __construct($cfg) {
        $this->log_level = $cfg['log_level'];
        $this->log_remain_days = $cfg['log_remain_days'];
        $this->set_log_file($cfg['log_path']);
        $this->log_relative_path = $cfg['log_relative_path'];
        if($this->log_relative_path){
            $this->project_path = realpath(APP_PATH.'/../').'/';
        }
        
        $arr = gettimeofday();
        $log_id = ($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF;
        $this->log_id = $log_id;
    }

    public function get_log_id(){
        return $this->log_id;
    }

    public function set_switch($flag){
        $this->log_switch = $flag;
    }

    /**
     * 单例模式使用log
     * @return self
     */
    static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new \poem\log(config());
        }
        return self::$instance;
    }

    /**
     * 写入日志
     * @param string $str 日志信息
     * @param string $lvl 日志级别
     * @param string $depth 深度，用于反查哪个文件打的日志
     * @return null
     */
    public function write($str, $lvl, $depth = 0) {
        if(!$this->log_switch) return;
        
        if ($lvl > $this->log_level) return;
        list($cur_file, $cur_line) = $this->get_trace_info($depth);
        
        $time = date('Y-m-d H:i:s');
        $log = "$time {$this->log_id} $cur_file:$cur_line $str" . PHP_EOL;

        self::trace('LOG', $log);

        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
        $lvl_name = $this->levels[$lvl];
        $lvl_info = $this->levels[self::INFO];
        file_put_contents($this->log_file.'.'.$lvl_info, $lvl_name.' '.$log, FILE_APPEND);
        if($lvl != self::INFO){
            file_put_contents($this->log_file.'.'.$lvl_name, $lvl_name.' '.$log, FILE_APPEND);
        }
    }

    /**
     * Undocumented function
     *
     * @param int $depth
     * @param bool $filter_poem_path
     * @return array
     */
    private function get_trace_info($depth){
        if($depth == self::DEPTH_FILTER_POEM){
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            foreach($trace as $v)
                if(stripos($v['file'], POEM_PATH)===false){
                    $cur_trace = $v;
                    break;
                }
        }else{
            $depth ++;
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
            $cur_trace = isset($trace[$depth]) ? $trace[$depth] : array('file'=>'','line'=>'');
        }

        $cur_file = $cur_trace['file'];
        $cur_line = $cur_trace['line'];
        if($this->log_relative_path){
            $cur_file = str_replace($this->project_path, '', $cur_file);
        }

        return array($cur_file, $cur_line);
    }

    /**
     * 设置日志文件
     * 通过 config.php 'log_path' 设置日志路径
     * 日志是按小时为文件名切割的，保留时间见 $this->clean_log()
     * @param string $log_dir 日志保存目录
     * @return void
     */
    private function set_log_file($log_dir) {
        if (empty($log_dir)) {
            $log_dir = config('runtime_path') . '/' . 'log';
        }
        $log_dir .= '/' . POEM_MODULE;
        
        $this->log_dir = $log_dir;
        $filename = date('YmdH') . '.log';
        $this->log_file = $log_dir . '/' . $filename;
    }

    /**
     * 清理日志，默认保留 1 天
     * 通过 config.php 'log_remain_days' 设置日志保留天数
     * @return void
     */
    public function clean_log() {
        $dh = opendir($this->log_dir);
        if (!$dh) {
            return;
        }
        while (false !== ($file = readdir($dh))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $full_path = $this->log_dir . '/' . $file;
            if (is_dir($full_path)) {
                continue;
            }
            $file_date = substr($file, 0, 8); // date('YmdH').log
            $cur_date = date('Ymd');

            $days = $cur_date - $file_date;
            if ($this->log_remain_days <= $days) {
                unlink($full_path);  ////删除文件
            }
        }
        closedir($dh); 
    }

    /**
     * 日志追踪，页面查看
     * @param string $key 键
     * @param string $value 值
     * @return null
     */
    static function trace($key, $value) {
        if (!config('debug_trace')) {
            return;
        }

        if (isset(self::$trace[$key]) && count(self::$trace[$key]) > 50) {
            return;
        }

        self::$trace[$key][] = $value;
    }

    /**
     * 请求结束,由框架保存
     * @return null
     */
    static function show() {
        $trace_tmp = self::$trace;
        $files     = get_included_files();
        foreach ($files as $key => $file) {
            $files[$key] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
        }
        $cltime           = T('POEM_TIME', -1);
        $trace_tmp['SYS'] = array(
            '请求信息' => $_SERVER['REQUEST_METHOD'] . ' ' . strip_tags($_SERVER['REQUEST_URI']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            '总吞吐量' => number_format(1 / $cltime, 2) . ' req/s',
            '总共时间' => number_format($cltime, 5) . ' s',
            '框架加载' => number_format(($cltime - T('POEM_EXEC_TIME', -1)), 5) . ' s (func:' . number_format(T('POEM_FUNC_TIME', -1) * 1000, 2) . 'ms conf:' . number_format(T('POEM_CONF_TIME', -1) * 1000, 2) . 'ms route:' . number_format(T('POEM_ROUTE_TIME', -1) * 1000, 2) . 'ms)',
            'App时间' => number_format(T('POEM_EXEC_TIME', -1), 5) . ' s (compile:' . number_format(T('POEM_COMPILE_TIME', -1) * 1000, 2) . ' ms)',
            '内存使用' => number_format(memory_get_usage() / 1024 / 1024, 5) . ' MB',
            '文件加载' => count($files),
            '会话信息' => 'SESSION_ID=' . session_id(),
        );

        $trace_tmp['FILE'] = $files;

        $arr = array(
            'SYS'  => '基本',
            'FILE' => '文件',
            'SQL'  => '数据库',
            'LOG'  => '日志',
        );
        foreach ($arr as $key => $value) {
            $num = 50;
            $len = 0;
            if (is_array($trace_tmp[$key]) && ($len = count($trace_tmp[$key])) > $num) {
                $trace_tmp[$key] = array_slice($trace_tmp[$key], 0, $num);
            }
            $trace[$value] = $trace_tmp[$key];
            if ($len > $num) {
                $trace[$value][] = "...... 共 $len 条";
            }

        }
        $totalTime = number_format($cltime, 3);
        include CORE_PATH . 'tpl/trace.php';
    }
}
