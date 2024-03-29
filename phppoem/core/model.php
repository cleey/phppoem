<?php
namespace poem;
class model {
    public $_db       = null; // 数据库资源
    protected $db_cfg = array(); // 数据库配置

    protected $_table    = '';
    protected $_distinct = '';
    protected $_field    = '*';
    protected $_join     = array();
    protected $_where    = array();
    protected $_group    = '';
    protected $_having   = '';
    protected $_order    = '';
    protected $_limit    = '';
    protected $_union    = '';
    protected $_lock     = '';
    protected $_comment  = '';
    protected $_force    = '';

    protected $_ismaster = false; // 针对查询，手动选择主库
    protected $_enable_cache_time = false; // 开启缓存
    protected $_enable_clear = true; // 是否清理所有条件，如果使用count 想保留条件继续查询就设为false
    protected $_empty_exception = false; // select/find 没有数据时抛出异常

    protected $_bind = array();
    protected $_sql  = '';

    /**
     * 构造函数
     * @param string $tb_name 表名
     * @param string/array $config 数据连接配置
     * string mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @return void
     */
    public function __construct($tb_name = '', $config = '') {

        if (empty($config)) {
            $config = config();
        }

        $this->db_cfg = array(
            'db_type'        => $config['db_type'],
            'db_host'        => $config['db_host'],
            'db_name'        => $config['db_name'],
            'db_user'        => $config['db_user'],
            'db_pass'        => $config['db_pass'],
            'db_port'        => $config['db_port'],
            'db_charset'     => $config['db_charset'],
            'db_deploy'      => $config['db_deploy'],
            'db_rw_separate' => $config['db_rw_separate'],
            'db_master_num'  => $config['db_master_num'],
            'db_slave_no'    => $config['db_slave_no'],
        );

        if ($tb_name != '') {
            $tb_name      = $config['db_prefix'] . $tb_name;
            $this->_table = $this->parse_tbname($tb_name);
        }
    }

    /**
     * 关闭数据
     * @return void
     */
    public function close() {
        db::get_instance($this->db_cfg)->_linkid = null;
    }

    /**
     * 获取当前sql
     * @return string $sql
     */
    public function sql() {
        return $this->_sql;
    }

    /**
     * 开始事务
     * @return void
     */
    public function begintransaction($name = '') {
        db::get_instance($this->db_cfg)->init_connect(true);

        return db::get_instance($this->db_cfg)->begintransaction($name);
    }

    /**
     * 回滚
     * @return void
     */
    public function rollback($name = '') {
        db::get_instance($this->db_cfg)->rollback($name);
    }

    /**
     * 提交事务
     * @return void
     */
    public function commit($name = '') {
        db::get_instance($this->db_cfg)->commit($name);
    }

    /**
     * 使用master
     * @return self
     */
    public function use_master() {
        $this->_ismaster = true;
        return $this;
    }

    /**
     * 不清理查询数据
     * @return self
     */
    public function no_clear() {
        $this->_enable_clear = false;
        return $this;
    }

    /**
     * 开启/关闭抛出异常，当select/find没有选出数据的时候
     * @return empty
     */
    public function empty_exception($enable=true) {
        $this->_empty_exception = $enable;
    }

    /**
     * 开启sql缓存
     *
     * @param integer $cache_time 缓存时间默认 3600s
     * @return self
     */
    public function cache($cache_time=3600){
        $this->_enable_cache_time = $cache_time;
        return $this;
    }

    /**
     * 执行sql查询
     * @param  string $sql
     * @param  array $bind 参数绑定
     * @return array $ret 二维查询结果
     */
    public function query($sql, $bind = array()) {
        return $this->query_with_cache($sql, $bind);
    }

    /**
     * query with cache check
     *
     * @param string $sql
     * @param array $bind
     * @return void
     */
    private function query_with_cache($sql, $bind) {
        // 1. check cache
        if($this->_enable_cache_time){
            $data = $this->get_db_cache($sql, $bind);
            if(!empty($data)){
                $this->after_sql('Cached');
                return $data;
            }
        }

        // 2. query db
        db::get_instance($this->db_cfg)->init_connect($this->_ismaster);
        $this->_sql = $sql;
        $info       = db::get_instance($this->db_cfg)->select($sql, $bind);

        $this->after_sql();
        if (empty($info) && $this->_empty_exception) {
            throw new \exception('empty data: '.$this->_sql);
        }

        // 3. cache data
        if($this->_enable_cache_time){
            $this->set_db_cache($sql, $bind, $info);
        }

        return $info;
    }

    /**
     * get db cache
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed
     */
    private function get_db_cache($sql, $bind){
        $real_uniq = $sql . serialize($bind);
        $cache_file = $this->get_db_cache_key($real_uniq);
        $cache_str = storage($cache_file);
        if(empty($cache_str)){ return false; }

        $split_pos = strrpos($cache_str,PHP_EOL);
        $cache_real_uniq = substr($cache_str, 0, $split_pos);
        if($real_uniq == $cache_real_uniq){
            $data_str = substr($cache_str, $split_pos+1);
            $data = unserialize($data_str);
            if(!empty($data)){
                return $data;
            }
            l('dbcache empty: '.$real_uniq);
        }else{
            l('dbcache key conflict: '.$real_uniq.' | '.$cache_real_uniq);
        }
        return false;
    }

    private function get_db_cache_key($real_uniq){
        if(config('storage_type') == \poem\cache\storage::TYPE_REDIS){
            return 'dbcache'.md5($real_uniq);
        }else{
            return '/dbcache/'.date('YmdH').'/'.md5($real_uniq);
        }
    }

    /**
     * set db cache
     *
     * @param strin $sql
     * @param mixed $bind
     * @param mixed $info
     * @return void
     */
    private function set_db_cache($sql, $bind, $info){
        $real_uniq = $sql . serialize($bind);
        $cache_file = $this->get_db_cache_key($real_uniq);

        $data = $real_uniq.PHP_EOL.serialize($info);
        storage($cache_file, $data, $this->_enable_cache_time );
        $this->_enable_cache_time = false;
    }

    /**
     * 执行sql语句
     * @param  string $sql
     * @return bool
     */
    public function exec($sql) {
        db::get_instance($this->db_cfg)->init_connect(true);

        $this->_sql = $sql;
        $info       = db::get_instance($this->db_cfg)->exec($sql);
        $this->after_sql();
        return $info;
    }

    /**
     * 设置自增
     * @param string $field 数据表字段
     * @param int $num 自增数
     * @return int $count 返回影响的函数
     */
    public function set_increase($field, $num = 1) {
        return $this->update("`{$field}`=`{$field}`+" . intval($num));
    }

    /**
     * 设置自减
     * @param string $field 数据表字段
     * @param int $num 自增数
     * @return int $count 返回影响的函数
     */
    public function set_decrease($field, $num = 1) {
        return $this->update("`{$field}`=`{$field}`-" . intval($num));
    }

    /**
     * sql distinct
     * @param  boolean $flag 是否开启distinct
     * @return self
     */
    public function distinct($flag = true) {
        $this->_distinct = $flag ? 'DISTINCT ' : '';
        return $this;
    }

    /**
     * sql select field
     * @param string $str 表字段 多个使用逗号隔开 'id,name,old'
     * @return self
     */
    public function field($str) {
        $this->_field = $str;
        return $this;
    }

    /**
     * get select field
     * @param string $str 返回 field() 设置的值
     * @return string select field
     */
    public function get_field() {
        return $this->_field;
    }

    /**
     * sql join
     * @param  string $str 表名
     * @param  string $type join类型
     * @return self
     */
    public function join($str, $type = 'INNER') {
        $this->_join[] = stristr($str, 'JOIN') ? $str : $type . ' JOIN ' . $str;
        return $this;
    }

    /**
     * sql where
     * @param array/string $arr where条件
     * @return self
     */
    public function where($arr) {
        if (is_string($arr)) {
            $this->_where['_string'] = $arr;
        } else {
            $this->_where = array_merge($this->_where, $arr);
        }

        return $this;
    }

    /**
     * sql set where
     * @param array/string $arr where条件
     * @return self
     */
    public function set_where($where) {
        $this->_where = $where;
        return $this;
    }

    /**
     * sql get where
     * @return array/string $arr where条件
     */
    public function get_where() {
        return $this->_where;
    }

    /**
     * sql having
     * @param  string $str 字符串
     * @return self
     */
    public function having($str) {
        $this->_having = $str;
        return $this;
    }

    /**
     * sql limit
     * @param  int $begin 开始
     * @param  int $end 结束
     * @return self
     */
    public function limit($begin = 0, $end = 0) {
        if ($end == 0) {
            $end   = $begin;
            $begin = 0;
        }
        $this->_limit = $begin;
        if ($end) {
            $this->_limit .= ",$end";
        }

        return $this;
    }

    /**
     * sql order
     * @param  string $str 表字段
     * @return self
     */
    public function order($str) {
        $this->_order = $str;
        return $this;
    }

    /**
     * sql group
     * @param  string $str 表字段
     * @return self
     */
    public function group($str) {
        $this->_group = $str;
        return $this;
    }

    /**
     * sql insert
     * @param  array $data 插入的表字段键值一维数组
     * @return bool $ret 成功/失败
     */
    public function insert($data = null) {
        if ($data == null) {return;}

        db::get_instance($this->db_cfg)->init_connect(true);
        // INSERT INTO more (id, NaMe) values (?, ?)
        $keys = '';
        $vals = '';
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            $keys .= "`$k`,";
            $vals .= ":$k,";
            $this->_bind[":$k"] = $v;
        }
        $keys       = substr($keys, 0, -1);
        $vals       = substr($vals, 0, -1);
        $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES ($vals)";
        $ret        = db::get_instance($this->db_cfg)->insert($this->_sql, $this->_bind);
        $this->after_sql();
        return $ret;
    }

    /**
     * 多组插入
     * @param array $data  插入的表字段键值二维数组
     * @param int $num 多少为一组插入,将$data分块
     * @return int $id 最后一次插入id
     */
    public function insert_multi($data = null, $num = 1000) {
        $first_row = current($data);
        if (!is_array($first_row)) {throw new \exception('insert_multi data must be array');}
        db::get_instance($this->db_cfg)->init_connect(true);

        $keys = implode(',', array_keys($first_row));
        $sql  = "insert into " . $this->_table . " ($keys) values";
        $vals = array();
        foreach ($data as $v) {
            $vals[] = '(' . implode(',', $this->parse_value($v)) . ')';
            if (count($vals) >= $num) {
                $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES " . implode(',', $vals);
                $info       = db::get_instance($this->db_cfg)->insert($this->_sql, $this->_bind);
                $vals       = array();
            }
        }
        if (count($vals)) {
            $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES " . implode(',', $vals);
            $info       = db::get_instance($this->db_cfg)->insert($this->_sql, $this->_bind);
        }
        $this->after_sql();
        return $info;
    }

    /**
     * 更新
     * @param  array $data 更新的表字段键值一维数组
     * @param  array $pk 主键字段
     * @return int $count 影响的行
     */
    public function update($data = null, $pk='id') {
        if ($data == null) {return;}
        db::get_instance($this->db_cfg)->init_connect(true);

        if (isset($data[$pk])) {
            $this->where(array($pk => $data[$pk]));
            unset($data[$pk]);
        }
        if (empty($this->_where)) {
            return false;
        }

        $keys = '';
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $kt = $this->parse_key($k);
                $keys .= "$kt=:$k,";
                $bind[":$k"] = $v;
            }
            $keys        = substr($keys, 0, -1);
            $this->_bind = array_merge($this->_bind, $bind);
        } elseif (is_string($data)) {
            $keys = $data;
        } else {
            throw new \exception('update params must be array or string');
        }

        $this->_sql = 'UPDATE ' . $this->_table . " SET {$keys}";
        $this->parse_where($this->_where);
        $info = db::get_instance($this->db_cfg)->update($this->_sql, $this->_bind);
        $this->after_sql();
        return $info;
    }

    /**
     * 删除
     * @return int $count 影响的行
     */
    public function delete() {
        db::get_instance($this->db_cfg)->init_connect(true);

        $this->_sql = 'DELETE FROM ' . $this->_table;

        // 防止误删
        if (empty($this->_where)) {
            throw new \Exception('delete sql need where:' . $this->_sql);
        }
        $this->parse_where($this->_where);
        $ret = db::get_instance($this->db_cfg)->delete($this->_sql, $this->_bind);
        $this->after_sql();
        return $ret;
    }

    /**
     * 查询
     * @return array $data 二维数组对应表行
     */
    public function select() {
        // $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
        $this->_sql = 'SELECT ' . $this->_distinct . $this->_field . ' FROM ' . $this->_table;
        $this->set_join($this->_join);
        $this->parse_where($this->_where);
        $this->set_group($this->_group);
        $this->set_having($this->_having);
        $this->set_order($this->_order);
        $this->set_limit($this->_limit);
        $this->set_union($this->_union);
        $this->set_lock($this->_lock);
        $this->set_comment($this->_comment);
        $this->set_force($this->_force);

        return $this->query_with_cache($this->_sql, $this->_bind);
    }

    /**
     * 统计行数
     * @return int $count
     */
    public function count() {
        db::get_instance($this->db_cfg)->init_connect(true);

        $this->_sql = 'SELECT count(*) as num FROM ' . $this->_table;
        $this->set_join($this->_join);
        $this->parse_where($this->_where);
        $this->set_group($this->_group);
        $this->set_order($this->_order);
        $this->set_limit($this->_limit);
        $info = db::get_instance($this->db_cfg)->select($this->_sql, $this->_bind);
        $this->after_sql();
        return $info[0]['num'];
    }

    /**
     * 查询一行
     * @return array $data 一维数组表字段键值对
     */
    public function find() {
        $info = $this->limit(1)->select();
        return $info[0];
    }

    /**
     * 根据ID查询
     * @param int $id
     * @return array $data 一维数组表字段键值对
     */
    public function id($id) {
        return $this->where(array('id' => $id))->find();
    }

    /**
     * 执行sql后，记录sql 并清理所有条件
     * @return void
     */
    protected function after_sql($prefix='') {
        foreach ($this->_bind as $key => $value) {
            $this->_sql = str_replace($key, db::get_instance($this->db_cfg)->_conn->quote($value), $this->_sql);
        }
        $time = number_format(T('poem_db_exec', -1) * 1000, 2);
        Log::trace('SQL', $this->_sql . "[{$time}ms]");
        l($prefix.'SQL: '. $this->_sql . "[{$time}ms]", \poem\log::INFO, \poem\log::DEPTH_FILTER_POEM);
        $this->_bind = array();
        if (!$this->_enable_clear) {
            $this->_enable_clear = true;
            return;
        }
        $this->clear();
    }

    /**
     * 清理所有条件
     * @return void
     */
    public function clear(){
        $this->_distinct = '';
        $this->_field    = '*';
        $this->_join     = array();
        $this->_where    = array();
        $this->_group    = '';
        $this->_having   = '';
        $this->_order    = '';
        $this->_limit    = '';
        $this->_union    = '';
        $this->_lock     = '';
        $this->_comment  = '';
        $this->_force    = '';
        $this->_ismaster = false;
    }

    /**
     * sql设置where
     * @param string $_where
     * @param bool $return_flag 是否换位
     * @return mixed
     */
    protected function parse_where($_where = null, $return_flag = false) {
        if ($_where == null) {
            return '';
        }

        $logic = 'AND';
        if (isset($_where['_logic'])) {
            $logic = strtoupper($_where['_logic']);
            unset($_where['_logic']);
        }

        $item = array();
        foreach ($_where as $k => $v) {
            if ($k == '_complex') {
                $item[] = substr($this->parse_where($v, true), 7);
            } elseif (is_array($v)) {
                $k   = $this->parse_key($k);
                $exp = strtoupper($v[0]); //  in like
                if (preg_match('/^(NOT IN|IN)$/', $exp)) {
                    if (is_string($v[1])) {
                        $v[1] = explode(',', $v[1]);
                    }

                    $vals   = implode(',', $this->parse_value($v[1]));
                    $item[] = "$k $exp ($vals)";
                } elseif (preg_match('/^(=|!=|<|<>|<=|>|>=)$/', $exp)) {
                    $k1                  = count($this->_bind);
                    $item[]              = "$k $exp :$k1";
                    $this->_bind[":$k1"] = $v[1];
                } elseif (preg_match('/^(BETWEEN|NOT BETWEEN)$/', $exp)) {
                    $tmp                 = is_string($v[1]) ? explode(',', $v[1]) : $v[1];
                    $k1                  = count($this->_bind);
                    $k2                  = $k1 + 1;
                    $item[]              = "$k $exp :$k1 AND :$k2";
                    $this->_bind[":$k1"] = $tmp[0];
                    $this->_bind[":$k2"] = $tmp[1];
                } elseif (preg_match('/^(LIKE|NOT LIKE)$/', $exp)) {
                    if (is_array($v[1])) {
                        $likeLogic = isset($v[2]) ? strtoupper($v[2]) : 'OR';
                        $like      = [];
                        foreach ($v[1] as $like_item) {
                            $like[] = "$k $exp " . $this->parse_value($like_item);
                        }

                        $str    = implode($likeLogic, $like);
                        $item[] = "($str)";
                    } else {
                        $wyk               = ':' . count($this->_bind);
                        $item[]            = "$k $exp $wyk";
                        $this->_bind[$wyk] = $v[1];
                    }
                } else {
                    throw new \Exception("exp error", 1);
                }
            } elseif ($k == '_string') {
                $item[] = $v;
            } else {
                $k                 = $this->parse_key($k);
                $wyk               = ':' . count($this->_bind);
                $item[]            = "$k=$wyk";
                $this->_bind[$wyk] = $v;
            }
        }

        $str = ' WHERE (' . implode(" $logic ", $item) . ')';
        if ($return_flag == true) {
            return $str;
        }

        $this->_sql .= $str;
    }

    /**
     * sql设置join
     * @param array $_join
     * @return void
     */
    public function set_join($_join) {
        if (empty($_join)) {
            return false;
        }

        $this->_sql .= ' ' . implode(' ', $_join);
    }

    /**
     * sql设置group
     * @param string $_group
     * @return void
     */
    public function set_group($_group) {
        if (empty($_group)) {
            return false;
        }

        $this->_sql .= ' GROUP BY ' . $_group;
    }

    /**
     * sql设置having
     * @param string $_having
     * @return void
     */
    public function set_having($_having) {
        if (empty($_having)) {
            return false;
        }

        $this->_sql .= ' HAVING ' . $_having;
    }

    /**
     * sql设置order
     * @param string $_order
     * @return void
     */
    public function set_order($_order) {
        if (empty($_order)) {
            return false;
        }

        $this->_sql .= ' ORDER BY ' . $_order;
    }

    /**
     * sql设置limit
     * @param string $_limit
     * @return void
     */
    public function set_limit($_limit) {
        if (empty($_limit)) {
            return false;
        }

        $this->_sql .= ' LIMIT ' . $_limit;
    }

    /**
     * todo sql 设置union
     * @param string $_union;
     * @return void
     */
    public function set_union($_union) {
    }

    /**
     * todo sql 设置lock
     * @param string $_lock;
     * @return void
     */
    public function set_lock($_lock) {
    }

    /**
     * todo sql 设置comment
     * @param string $_comment;
     * @return void
     */
    public function set_comment($_comment) {
    }

    /**
     * todo sql 设置force
     * @param string $_force;
     * @return void
     */
    public function set_force($_force) {
    }

    /**
     * sql格式化字段值
     * @param  mixed $val 字段
     * @return mixed $val 字段
     */
    protected function parse_value($val) {
        $type = gettype($val);
        switch ($type) {
            case 'string':return db::get_instance($this->db_cfg)->_conn->quote($val);
            case 'array':return array_map([$this, 'parse_value'], $val);
            case 'boolean':return $val ? 1 : 0;
            case 'NULL':return 'null';
            case 'integer':return $val;
            default:throw new \exception('sql parse_value not allow:' . $type);
        }
    }

    /**
     * sql格式化字段
     * @param  string $key 字段
     * @return string $key 字段
     */
    protected function parse_key($key) {
        if ($key[0] == '`') {return;}
        if ($pos = strpos($key, '.')) {
            $key = '`' . substr_replace($key, '`.`', $pos, 1) . '`';
        } else {
            $key = "`$key`";
        }
        return $key;
    }

    /**
     * sql格式化表名
     * @param  string $tb 表名
     * @return string $tb sql格式表名
     */
    private function parse_tbname($tb) {
        if ($tb[0] == '`') {return;}
        if ($pos = strpos($tb, ' ')) {
            $tb = '`' . substr_replace($tb, '` ', $pos, 1);
        } elseif ($pos = strpos($tb, '.')) {
            $tb = '`' . substr_replace($tb, '`.', $pos, 1);
        } else {
            $tb = "`$tb`";
        }
        return $tb;
    }
}
