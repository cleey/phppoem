<?php
namespace poem\cache;

class redis {
    public $_ins;
    protected $_option;
    function __construct($option = array()) {
        $option = array_merge(array(
            'host'    => config('redis_host') ?: '127.0.0.1',
            'port'    => config('redis_port') ?: 6379,
            'expire'  => config('redis_expire') ?: null,
            'auth'    => config('redis_auth') ?: 0,
            'timeout' => config('redis_timeout') ?: 0,
        ), $option);
        $this->_option = $option;
        $this->_ins    = new \Redis;
        $re            = $this->_ins->connect($option['host'], $option['port'], $option['timeout']);
        if (!$re) {
            throw new \Exception("Connect Redis Failed", 1);
        }

        if ($option['auth']) {
            $this->_ins->auth($option['auth']);
        }

    }

    public function get($key) {
        $data = $this->_ins->get($key);
        $json = json_decode($data, true);
        return $json === NULL ? $data : $json;
    }

    public function set($key, $value, $expire = null) {
        if (is_null($expire)) {
            $expire = $this->_option['expire'];
        }

        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return is_int($expire) ? $this->_ins->set($key, $value) : $this->_ins->setex($key, $expire, $value);
    }

    public function del($key) {
        return $this->_ins->del($key);
    }

    function __destruct() {$this->_ins->close();}
}