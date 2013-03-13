<?php
/*
说明：数据库主从操作。
功能：对不同的sql语句，操作不同的库，实现主从分离。
作者：huang.liang@neusoft.com
最后更新：2012-08-09
*/

visit_limit();

class MasterSlave extends DataBase {
	private $masterConfig;	# 主库配置项内容
	private $slaveConfig;	# 从库配置项内容

	private $masterPDO;	# 主库PDO对象
	private $slavePDO;	# 从库PDO对象

	private $dbSelect = null;

	public function __construct($config) {
		if (isset($config['Master']) && isset($config['Slave'])) {
			$this->masterConfig = $config['Master'];
			$this->slaveConfig = $config['Slave'];
		} else {
			die('配置文件错误');
		}
	}

	# 切换数据库连接
	public function changeDbConn() {
		if (is_null($this->dbSelect)) {
			switch($this->sqlType) {
				case 'insert':
				case 'delete':
				case 'update':
					$this->createMasterPDO();
					break;
				case 'select':
					$this->createSlavePDO();
					break;
			}
		} elseif ($this->dbSelect == 'master') {
			$this->createMasterPDO();
		} elseif ($this->dbSelect == 'slave') {
			$this->createSlavePDO();
		}
	}

	public function createMasterPDO() {
		if (!is_object($this->masterPDO)) {
			$dsn = "{$this->masterConfig['db_driver']}:host={$this->masterConfig['db_server']};port={$this->masterConfig['db_port']};dbname={$this->masterConfig['db_name']};charset={$this->masterConfig['db_charset']}";
			$this->masterPDO = parent::__construct($dsn, $this->masterConfig);
		}
		$this->pdObj = $this->masterPDO;
	}

	public function createSlavePDO() {
		if (!is_object($this->slavePDO)) {
			$dsn = "{$this->slaveConfig['db_driver']}:host={$this->slaveConfig['db_server']};port={$this->slaveConfig['db_port']};dbname={$this->slaveConfig['db_name']};charset={$this->slaveConfig['db_charset']}";
			$this->slavePDO = parent::__construct($dsn, $this->slaveConfig);
		}
		$this->pdObj = $this->slavePDO;
	}

	# 执行SQL语句
	public function exec($sql) {
		parent::setSql($sql);
		$this->changeDbConn($this->dbSelect);
		return parent::exec($sql);
	}

	/*public function query($sql) {
		return $this->exec($sql);
	}*/

	# 绑定准备
	public function prepare($sqlTemplate) {
		try {
			parent::setSql($sqlTemplate);
			$this->changeDbConn($this->dbSelect);
			return parent::prepare($sqlTemplate);
		} catch (Exception $e) {
			trigger_error('数据库异常：' . $e->getMessage(), E_USER_ERROR);
		}
	}

	public function beginTa() {
		try {
			$this->dbSelect = 'master';
			$this->changeDbConn($this->dbSelect);
			return parent::beginTa();
		} catch (Exception $e) {
			trigger_error('数据库异常：' . $e->getMessage(), E_USER_ERROR);
		}
	}

	public function commitTa() {
		try {
			$this->dbSelect = null;
			return parent::commitTa();
		} catch (Exception $e) {
			trigger_error('数据库异常：' . $e->getMessage(), E_USER_ERROR);
		}
	}
}
