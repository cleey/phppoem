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
    protected $_noclear  = false; // 针对count，不用清空

    protected $_bind = array();
    protected $_sql  = '';

    function __construct($tb_name = '', $config = '') {
        if ($tb_name != '') {
            $tb_name      = config('db_prefix') . $tb_name;
            $this->_table = $this->parseTbName($tb_name);
        }

        if ($config === '') {
            // 配置文件
            if ($dsn = config('db_dsn')) {
                $this->db_cfg = $dsn;
            } else {
                $this->db_cfg = array(
                    'db_type'        => config('db_type'),
                    'db_host'        => config('db_host'),
                    'db_name'        => config('db_name'),
                    'db_user'        => config('db_user'),
                    'db_pass'        => config('db_pass'),
                    'db_port'        => config('db_port'),
                    'db_charset'     => config('db_charset'),
                    'db_deploy'      => config('db_deploy'),
                    'db_rw_separate' => config('db_rw_separate'),
                    'db_master_num'  => config('db_master_num'),
                    'db_slave_no'    => config('db_slave_no'),
                );
            }
        } else {
            // 用户指定配置
            $this->db_cfg = $config;
        }
    }
    function close() {
        Db::getIns($this->db_cfg)->_linkid = null;
    }

    function sql() {
        return $this->_sql;
    }

    function beginTransaction() {
        Db::getIns($this->db_cfg)->init_connect(true);

        Db::getIns($this->db_cfg)->beginTransaction();
    }
    function rollBack() {
        Db::getIns($this->db_cfg)->rollBack();
    }
    function commit() {
        Db::getIns($this->db_cfg)->commit();
    }
    function master() {
        $this->_ismaster = true;
        return $this;
    }
    function noclear() {
        $this->_noclear = true;
        return $this;
    }

    function query($sql, $bind = array()) {
        Db::getIns($this->db_cfg)->init_connect($this->_ismaster);
        $this->_sql = $sql;
        $info       = Db::getIns($this->db_cfg)->select($sql, $bind);
        $this->afterSql();
        return $info;
    }
    function exec($sql) {
        Db::getIns($this->db_cfg)->init_connect(true);

        $this->_sql = $sql;
        $info       = Db::getIns($this->db_cfg)->exec($sql);
        $this->afterSql();
        return $info;
    }
    function setInc($field, $num) {
        return $this->update("{$field}={$field}+" . intval($num));
    }
    function setDec($field, $num) {
        return $this->update("{$field}={$field}-" . intval($num));
    }

    function bind($val) {
        $key                  = count($this->_bind);
        $this->_bind[":$key"] = $val;
        return $this;
    }

    function distinct($flag = true) {
        $this->_distinct = $flag ? 'DISTINCT ' : '';
        return $this;
    }

    function field($str) {
        $this->_field = $str;
        return $this;
    }

    function join($str, $type = 'INNER') {
        $this->_join[] = stristr($str, 'JOIN') ? $str : $type . ' JOIN ' . $str;
        return $this;
    }

    function where($arr) {
        if (is_string($arr)) {
            $this->_where['_string'] = $arr;
        } else {
            $this->_where = array_merge($this->_where, $arr);
        }

        return $this;
    }

    function having($str) {
        $this->_having = $str;
        return $this;
    }

    function limit($b = 0, $e = 0) {
        if ($e == 0) {
            $e = $b;
            $b = 0;}
        $this->_limit = $b;
        if ($e) {
            $this->_limit .= ",$e";
        }

        return $this;
    }

    function order($str) {
        $this->_order = $str;
        return $this;
    }
    function group($str) {
        $this->_group = $str;
        return $this;
    }

    function insert($data = null) {
        if ($data == null) {return;}

        Db::getIns($this->db_cfg)->init_connect(true);
        // INSERT INTO more (id, NaMe) values (?, ?)
        $keys = '';
        $vals = '';
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            $keys .= "$k,";
            $vals .= ":$k,";
            $this->_bind[":$k"] = $v;
        }
        $keys       = substr($keys, 0, -1);
        $vals       = substr($vals, 0, -1);
        $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES ($vals)";
        $info       = Db::getIns($this->db_cfg)->insert($this->_sql, $this->_bind);
        $this->afterSql();
        return $info;
    }
    function insertAll($data = null, $num = 1000) {
        if (!is_array($data[0])) {return false;}
        Db::getIns($this->db_cfg)->init_connect(true);

        $keys = implode(',', array_keys($data[0]));
        $sql  = "insert into " . $this->_table . " ($keys) values";
        $vals = array();
        foreach ($data as $v) {
            $vals[] = '(' . implode(',', $this->parseValue($v)) . ')';
            if (count($vals) >= $num) {
                $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES " . implode(',', $vals);
                $info       = Db::getIns($this->db_cfg)->insert($this->_sql, $this->_bind);
                $vals       = array();
            }
        }
        if (count($vals)) {
            $this->_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES " . implode(',', $vals);
            $info       = Db::getIns($this->db_cfg)->insert($this->_sql, $this->_bind);
        }
        $this->afterSql();
        return $info;
    }

    function update($data = null) {
        if ($data == null) {return;}
        Db::getIns($this->db_cfg)->init_connect(true);

        if (isset($data['id'])) {
            $this->where(array('id' => $data['id']));
            unset($data['id']);
        }
        if (empty($this->_where)) {
            return false;
        }

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $kt = $this->parseKey($k);
                $keys .= "$kt=:$k,";
                $bind[":$k"] = $v;
            }
            $keys        = substr($keys, 0, -1);
            $this->_bind = array_merge($this->_bind, $bind);
        } elseif (is_string($data)) {
            $keys = $data;
        } else {
            new \Exception('update params must be array or string');
        }

        $this->_sql = 'UPDATE ' . $this->_table . " SET {$keys}";
        $this->setWhere($this->_where);
        $info = Db::getIns($this->db_cfg)->update($this->_sql, $this->_bind);
        $this->afterSql();
        return $info;
    }

    function delete() {
        Db::getIns($this->db_cfg)->init_connect(true);

        $this->_sql = 'DELETE FROM ' . $this->_table;
        $this->setWhere($this->_where);
        $info = Db::getIns($this->db_cfg)->delete($this->_sql, $this->_bind);
        $this->afterSql();
        return $info;
    }

    function select() {
        Db::getIns($this->db_cfg)->init_connect($this->_ismaster);

        // $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
        $this->_sql = 'SELECT ' . $this->_distinct . $this->_field . ' FROM ' . $this->_table;
        $this->setJoin($this->_join);
        $this->setWhere($this->_where);
        $this->setGroup($this->_group);
        $this->setHaving($this->_having);
        $this->setOrder($this->_order);
        $this->setLimit($this->_limit);
        $this->setUnion($this->_union);
        $this->setLock($this->_lock);
        $this->setComment($this->_comment);
        $this->setForce($this->_force);

        $info = Db::getIns($this->db_cfg)->select($this->_sql, $this->_bind);
        $this->afterSql();
        return $info;
    }

    function count() {
        Db::getIns($this->db_cfg)->init_connect(true);

        $this->_sql = 'SELECT count(*) as num FROM ' . $this->_table;
        $this->setJoin($this->_join);
        $this->setWhere($this->_where);
        $this->setGroup($this->_group);
        $this->setOrder($this->_order);
        $this->setLimit($this->_limit);
        $info = Db::getIns($this->db_cfg)->select($this->_sql, $this->_bind);
        $this->afterSql();
        return $info[0]['num'];
    }

    function find() {
        $info = $this->select();
        return $info[0];
    }

    function id($id) {
        return $this->where(array('id' => $id))->find();
    }

    protected function afterSql() {
        foreach ($this->_bind as $key => $value) {
            $this->_sql = str_replace($key, Db::getIns($this->db_cfg)->_conn->quote($value), $this->_sql);
        }
        $time = number_format(T('poem_db_exec', -1) * 1000, 2);
        Log::trace('SQL', $this->_sql . "[{$time}ms]");
        $this->_bind = array();
        if ($this->_noclear) {
            $this->_noclear = false;
            return;
        }
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

    protected function setWhere($_where = null, $flag = false) {
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
                $item[] = substr($this->setWhere($v, true), 7);
            } elseif (is_array($v)) {
                $k   = $this->parseKey($k);
                $exp = strtoupper($v[0]); //  in like
                if (preg_match('/^(NOT IN|IN)$/', $exp)) {
                    if (is_string($v[1])) {
                        $v[1] = explode(',', $v[1]);
                    }

                    $vals   = implode(',', $this->parseValue($v[1]));
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
                            $like[] = "$k $exp " . $this->parseValue($like_item);
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
                $k                 = $this->parseKey($k);
                $wyk               = ':' . count($this->_bind);
                $item[]            = "$k=$wyk";
                $this->_bind[$wyk] = $v;
            }
        }

        $str = ' WHERE (' . implode(" $logic ", $item) . ')';
        if ($flag == true) {
            return $str;
        }

        $this->_sql .= $str;
    }

    function setJoin($_join) {
        if (empty($_join)) {
            return false;
        }

        $this->_sql .= ' ' . implode(' ', $_join);
    }

    function setGroup($_group) {
        if (empty($this->_group)) {
            return false;
        }

        $this->_sql .= ' GROUP BY ' . $this->_group;
    }
    function setHaving($_having) {
        if (empty($this->_having)) {
            return false;
        }

        $this->_sql .= ' HAVING ' . $this->_having;
    }
    function setOrder($_order) {
        if (empty($this->_order)) {
            return false;
        }

        $this->_sql .= ' ORDER BY ' . $this->_order;
    }
    function setLimit($_limit) {
        if (empty($this->_limit)) {
            return false;
        }

        $this->_sql .= ' LIMIT ' . $this->_limit;
    }
    function setUnion($_union) {
        return '';
    }
    function setLock($_lock) {
        return '';
    }
    function setComment($_comment) {
        return '';
    }
    function setForce($_force) {
        return '';
    }

    protected function parseValue($val) {
        if (is_string($val)) {
            return Db::getIns($this->db_cfg)->_conn->quote($val);
        } elseif (is_array($val)) {
            return array_map([$this, 'parseValue'], $val);
        } elseif (is_bool($val)) {
            return $val ? 1 : 0;
        } elseif (is_null($val)) {
            return 'null';
        } else {
            return $val;
        }
    }

    protected function parseKey($key) {
        if ($key[0] == '`') {return;}
        if ($pos = strpos($key, '.')) {
            $key = '`' . substr_replace($key, '`.`', $pos, 1) . '`';
        } else {
            $key = "`$key`";
        }
        return $key;
    }

    private function parseTbName($tb) {
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