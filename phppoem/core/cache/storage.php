<?php
namespace poem\cache;

class storage {
    public $_ins;
    protected $_option;

    const TYPE_REDIS = 'redis';
    const TYPE_FILE = 'file';

    /**
     * 构造函数
     * @param array $option redis配置
     * @return null
     */
    public function __construct($option = array()) {
        $option = array_merge(array(
            'type'    => config('storage_type'), // redis or storage
            'expire'  => config('storage_expire'),
            'compress'  => config('storage_compress'),
        ), $option);

        $this->_option = $option;
        $type = '\\poem\\cache\\'.$option['type'];
        $this->_ins    = new $type();
    }

    /**
     * 获取键key
     * @param string $key 键
     * @return string 数据
     */
    public function get($key) {
        $data = $this->_ins->get($key);
        if($this->_option['compress'] && function_exists('gzdeflate')){
            return gzinflate($data);
        }
        return $data;
    }

    /**
     * 设置键值并含过期时间
     * @param string $key 键
     * @param string $value 值
     * @param string $expire 过期时间
     * @return string 文件路径
     */
    public function set_expire($key, $value, $expire = null) {
        if (is_null($expire)) {
            $expire = $this->_option['expire'];
        }
        if($this->_option['compress'] && function_exists('gzdeflate')){
            $value = gzdeflate($value);
        }

        return $this->_ins->set_expire($key, $value, $expire);
    }

    /**
     * 删除键值
     * @param string $key 键
     * @return bool 
     */
    public function del($key) {
        return $this->_ins->del($key);
    }
}