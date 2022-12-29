<?php
namespace poem\cache;
/**
 * 文件存储键值对
 */
class file {

    /**
     * 检查key是否存在
     * @param string $key 键
     * @return boolean 存在true,否则false
     */
    public function has($key) {
        $key = config('runtime_path') . '/' . $key;
        return is_file($key) ? $key : false;
    }

    /**
     * 获取key的值
     * @param string $key 键
     * @return string 值
     */
    public function get($key) {
        $file = config('runtime_path') . '/' . $key;
        $file_timeout = $file.'.t';
        if (!is_file($file)) return false;

        // 超时
        if(is_file($file_timeout)){
            $time = file_get_contents($file_timeout);
            if($time < time()){
                $this->del($file);
                $this->del($file_timeout);
                return false;
            }
        }

        return file_get_contents($file);
    }

    /**
     * 设置键值
     * @param string $key 键
     * @param string $value 值
     * @return 返回设置的文件路径
     */
    public function set($key, $value) {
        $key = config('runtime_path') . '/' . $key;
        $dir = dirname($key);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $re = file_put_contents($key, $value);
      
        if (!$re) {
            $str = 'storage cache write failed：' . $key;
            l($str, \poem\log::INFO, \poem\log::DEPTH_FILTER_POEM);
            throw new \exception($str);
        }
        return $key;
    }

    public function set_expire($key, $value, $expire) {
        $this->set($key, $value);
        $this->set($key.'.t',time()+$expire);
    }

    /**
     * 删除key
     * @param string $key 键
     * @return null
     */
    public function del($key) {
        $file = config('runtime_path') . '/' . $key;
        $file_tiemout = config('runtime_path') . '/' . $key;
        if (is_file($file)) {
            unlink($file);
        }
        if (is_file($file_tiemout)) {
            unlink($file_tiemout);
        }
    }
}