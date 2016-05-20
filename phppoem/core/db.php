<?php
namespace poem;

class db{

	private static $_ins = array();
	public $_linkid = array();
	public $_conn = null;
	protected $_cfg;
	protected $options = array( \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION );

	static function getIns($config){
		$key = md5( is_array($config)?serialize($config):$config );
		if( !isset(self::$_ins[$key]) || !(self::$_ins[$key] instanceof self) ){
			self::$_ins[$key] = new self();
			self::$_ins[$key]->_cfg = $config;
			if( !is_string($config) && isset($config['db_deploy']) && !empty($config['db_deploy']) ){
				self::$_ins[$key]->parseCfg();
			}
		}
		return self::$_ins[$key];
	}

	public function init_connect($master = true){
        if ( !is_string($this->_cfg) && isset($this->_cfg['db_deploy']) && !empty($this->_cfg['db_deploy']) )
            $this->_conn = $this->deployConnect($master); // 采用分布式数据库
        else
            $this->_conn = $this->connect(); // 默认单数据库
    }

    private function parseCfg(){
    	$this->_cfg['db_user']   = explode(',', $this->_cfg['db_user']);
        $this->_cfg['db_pass']   = explode(',', $this->_cfg['db_pass']);
        $this->_cfg['db_host']   = explode(',', $this->_cfg['db_host']);
        $this->_cfg['db_port']   = explode(',', $this->_cfg['db_port']);
        $this->_cfg['db_name']   = explode(',', $this->_cfg['db_name']);
        $this->_cfg['db_charset']= explode(',', $this->_cfg['db_charset']);
    }

    protected function deployConnect($master = false){
        // 分布式数据库配置解析
        $conf = $this->_cfg;

        // 数据库读写是否分离
        if ($conf['db_rw_separate']) {
            if( $master ) $id = mt_rand(0,$this->_cfg['db_master_num']-1);
            else{
            	if( is_numeric($conf['db_slave_no']) ) $id = $conf['db_slave_no'];
            	else $id = mt_rand($conf['db_master_num'],count($conf['db_host'])-1 );
            }
        } else { // 读写操作不区分服务器
            $id = mt_rand(0, count($conf['db_host'])-1 ); // 每次随机连接的数据库
        }

        $id_config = array(
            'db_type'   => $conf['db_type'],
            'db_user'   => isset($conf['db_user'][$id])   ? $conf['db_user'][$id]   : $conf['db_user'][0],
            'db_pass'   => isset($conf['db_pass'][$id])   ? $conf['db_pass'][$id]   : $conf['db_pass'][0],
            'db_host'   => isset($conf['db_host'][$id])   ? $conf['db_host'][$id]   : $conf['db_host'][0],
            'db_port'   => isset($conf['db_port'][$id])   ? $conf['db_port'][$id]   : $conf['db_port'][0],
            'db_name'   => isset($conf['db_name'][$id])   ? $conf['db_name'][$id]   : $conf['db_name'][0],
            'db_charset'=> isset($conf['db_charset'][$id])? $conf['db_charset'][$id]: $conf['db_charset'][0],
        );
        // co($id_config,$id,$master);
        return $this->connect($id_config, $id, $master);
    }

	private function connect($config='',$linkid=0,$reconnect=false){
		if( !isset($this->_linkid[$linkid]) ){
			$dsn = $this->parseDsn($config);
			if( $dsn['char'] ) $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '".$dsn['char']."'";
			T('poem_db_exec');
			try{
				$this->_linkid[$linkid] = new \PDO($dsn['dsn'],$dsn['user'],$dsn['pass'], $this->options) or die('数据库连接失败');
				$time = number_format(T('poem_db_exec',1)*1000,2);
			}catch(\PDOException $e){
				if( $reconnect ){
					Log::trace('ERR',$e->getMessage());
					$this->connect($config,$linkid);
				}else{
					throw new \Exception($e->getMessage());
				}
			}
			Log::trace('SQL',"PDO连接 [{$time}ms]");
		}
		return $this->_linkid[$linkid];
	}

	private function parseDsn($config=''){
		if( $config == '' ) $config = $this->_cfg;
		$char = '';
		if( is_array($config) ){
			$type = $config['db_type'];
			$host = $config['db_host'];
			$port = $config['db_port'];
			$name = $config['db_name'];
			$user = $config['db_user'];
			$pass = $config['db_pass'];
			$char = $config['db_charset'];
			$dsn = "{$type}:host={$host};port={$port};dbname={$name};charset={$char}";
		}else{
			$tmp = explode('@',$config);
			if( count($tmp) == 2 ){
				list($user,$pass) = explode(':',$tmp[0]);
				$dsn = $tmp[1];
			}else{
				$dsn = $config;
			}
		}
		return array('user' => $user, 'pass' => $pass, 'char' => $char, 'dsn' => $dsn);
	}

	function close(){ $this->_conn = NULL; }

	function beginTransaction(){ return $this->_conn->beginTransaction(); }
	function rollBack(){ return $this->_conn->rollBack(); }
	function commit(){ return $this->_conn->commit(); }

	function exec($sql){
		T('poem_db_exec');
		try{
			$re = $this->_conn->exec($sql);
			T('poem_db_exec',0);
			return $re;
		}catch(\PDOException $e){
			$this->error($e,$sql);
		}
	}
	function select($sql,$bind){ return $this->execute($sql,$bind,'select'); }
	function insert($sql,$bind){ return $this->execute($sql,$bind,'insert'); }
	function update($sql,$bind){ return $this->execute($sql,$bind,'update'); }
	function delete($sql,$bind){ return $this->execute($sql,$bind,'delete'); }

	private function execute($sql,$bind,$flag=''){
		T('poem_db_exec');
		try{
			$pre = $this->_conn->prepare($sql);
			if( !$pre ) $this->error($this->_conn,$sql);
			foreach ($bind as $k => $v) $pre->bindValue($k,$v);
			$re = $pre->execute();
			if(!$re) $this->error($pre,$sql);

			T('poem_db_exec',0);
			switch ($flag) {
				case 'insert': return $this->_conn->lastInsertId(); break;
				case 'update': return $pre->rowCount(); break;
				case 'delete': return $pre->rowCount(); break;
				case 'select': return $pre->fetchAll(\PDO::FETCH_ASSOC); break;
				default: break;
			}
		}catch(\PDOException $e){
			$this->error($e,$sql);
		}
	}
	private function error($e,$sql){
		throw new \Exception(implode(', ', $e->errorInfo)."\n [SQL 语句]：".$sql);
	}
	function __destruct(){
		$this->_linkid = null;
		$this->_conn = null;
	}
}

?>