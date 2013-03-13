<?php
/*
说明：数据库操作。
功能：通过pdo，实现对数据库的单库操作。
作者：huang.liang@neusoft.com
最后更新：2012-08-09
*/

/*if (!defined('LEAP_START')) {
	trigger_error('Access denied !', E_USER_ERROR);
}
*/
visit_limit();

class DataBase extends Base {
	protected $configure;	# 数据库配置
	protected $pdObj; # PDO连接对象

	protected $sql;	# SQL语句
	protected $sqlType;	# SQL语句类型
	protected $transaction = false;	# 事务开关
	protected $transErrorPool = array();	# 事务查询结果池

	private $sqlBindStd;	# 变量绑定对象

	public function __construct($dsn, $config) {
		try {
			if (!is_object($this->pdObj)) {
				$this->pdObj = new PDO($dsn, $config['db_user'], $config['db_pass'], array(PDO::ATTR_PERSISTENT=>false));
				return $this->pdObj;
			}
		} catch (Exception $e) {
			throw new Exception('Could not connect to the database. ' . $e->getMessage(), 824209002);
			
		}
	}

	public function __destruct() {
		if (is_object($this->pdObj)) {
			$this->pdObj = null;
		}
	}

	# 设置SQL语句、语句类型
	protected function setSql($sql) {
		if (!is_null($sql)) {
			$this->sql = $sql;
			$this->sqlType = substr(strtolower($sql), 0, 6);
		}
	}

	# 取当前执行操作的SQL语句或模板
	public function getSql() {
		return $this->sql;
	}

	# 设置事务开关
	protected function setTransaction($transaction) {
		$this->transaction = $transaction;
	}

	public function getTransaction() {
		return $this->transaction;
	}

	# 返回事务查询过程中的错误语句
	public function getTransErrorPool() {
		return $this->transErrorPool;
	}

	# 返回查询结果值
	protected function getReturnValue($_resource, $stdObj = '') {
		switch($this->sqlType) {
			case 'insert':
				if ($_resource == false) {
					$_result = false;
				} elseif ($_resource == 1) {
					$_result = $this->pdObj->lastInsertId();
				} else {
					$_result = true;
				}
				break;
			case 'delete':
				if ($_resource === false) {
					$_result = false;
				} else {
					$_result = true;
				}
				break;
			case 'update':
				if ($_resource === false) {
					$_result = false;
				} else {
					$_result = true;
				}
				break;
			case 'select':
				if (is_object($stdObj)) {
					$_result = $stdObj->fetchAll(PDO::FETCH_ASSOC);
				} else {
					if (is_object($_resource)) {
						$_result = $_resource->fetchAll();
					} else {
						throw new Exception('SELECT query error while execute sql.', 824209010);
					}
				}
				break;
		}
		return $_result;
	}

	# 执行SQL语句
	public function exec($sql = '') {
		list($sql) = LeapHook_add('DbExecBefore', array($sql));
		$this->setSql($sql);
		if (in_array($this->sqlType, array('select', 'insert', 'update', 'delete'))) {
			$query_func_name = $this->sqlType;
			$_result = self::$query_func_name();
			if ($this->transaction == true && $_resource === false) {
				array_push($this->transErrorPool, $this->sql);
			}
		} else {
			if (isset($sql)) {
				$_result = $this->pdObj->exec($sql);
			} else {
				throw new Exception("Trying to query an empty sql.", 824209011);
			}
		}
		list($_result) = LeapHook_add('DbExecAfter', array($_result));
		return $_result;
	}

	# exec方法的别名
	public function query($sql, $transaction = false) {
		return $this->exec($sql, $transaction);
	}

	# 插入语句
	public function insert($sql = null) {
		try {
			$this->setSql($sql);
			$_resource = $this->pdObj->exec($this->sql);
			return $this->getReturnValue($_resource);
		} catch (Exception $e) {
			throw new Exception('INSERT query error while execute sql.', 824209014);
		}
	}

	# 删除语句
	public function delete($sql = null) {
		try {
			$this->setSql($sql);
			$_resource = $this->pdObj->exec($this->sql);
			return $this->getReturnValue($_resource);
		} catch (Exception $e) {
			throw new Exception('DELETE query error while execute sql.', 824209013);
		}
	}

	# 修改语句
	public function update($sql = null) {
		try {
			$this->setSql($sql);
			$_resource = $this->pdObj->exec($this->sql);
			return $this->getReturnValue($_resource);
		} catch (Exception $e) {
			throw new Exception('UPDATE query error while execute sql.', 824209012);
		}
	}

	# 查询语句
	public function select($sql = null) {
		try {
			$this->setSql($sql);
			$_resource = $this->pdObj->query($this->sql, PDO::FETCH_ASSOC);
			return $this->getReturnValue($_resource);
		} catch (Exception $e) {
			throw new Exception('SELECT query error while execute sql.', 824209010);
		}
	}

	# 变量绑定，准备SQL语句模板
	public function prepare($sqlTemplate) {
		try {
			list($sqlTemplate) = LeapHook_add('DbPrepareBefore', array($sqlTemplate));
			$this->setSql($sqlTemplate);
			if ($this->sqlBindStd = $this->pdObj->prepare($sqlTemplate)) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			throw new Exception('Error on preparing sql template.', 824209003);
		}
	}

	# 绑定变量，并返回执行结果
	# 一维数组或二维数组
	public function execBind($bindArray = '') {
		if (is_array($bindArray)) {
			if (isset($bindArray[0])) {
				$_result = array();
				foreach ($bindArray as $key => $value) {
					array_push($_result, $this->execBindSingle($value));
				}
				return $_result;
			} else {
				return $this->execBindSingle($bindArray);
			}
		} else {
			throw new Exception('Value(s) prepared to bind is not match.', 824209004);
		}
	}

	# 单条绑定变量
	protected function execBindSingle($singleArray) {
		if (is_array($singleArray)) {
			foreach ($singleArray as $key => $value) {

				$blocks = explode('_', $key);
				$bind_type = array_pop($blocks);
				$bind_statuement = implode('_', $blocks);

				switch ($bind_type) {
					case 'int':
						if (!is_numeric($value)) {
							throw new Exception('Bind value is not a numeric value.', 824209005);
						}
						$_bindParamType = PDO::PARAM_INT;
						break;
					case 'str':
						if (!is_string($value) && !is_numeric($value)) {
							throw new Exception('Bind value is not a string value.', 824209005);
						}
						$_bindParamType = PDO::PARAM_STR;
						break;
					case 'bool':
						if (!is_bool($value)) {
							throw new Exception('Bind value is not a bool value.', 824209005);
						}
						$_bindParamType = PDO::PARAM_BOOL;
						break;
					case 'null':
						if (!is_null($value)) {
							throw new Exception('Bind value is not a null value.', 824209005);
						}
						$_bindParamType = PDO::PARAM_NULL;
						break;
					default:
						throw new Exception('Unknown bind value type.', 824209005);
						break;
				}

				try {
					$this->sqlBindStd->bindValue($bind_statuement, $value, $_bindParamType);
				} catch (Exception $e) {
					throw new Exception('Error on bind value(s) to sql template.', 824209005);
				}
			}
			try {
				$_resource = $this->sqlBindStd->execute();
			} catch (Exception $e) {
				/*if (DEBUG) {
					$ext_sql = $this->getSql();
				}*/
				throw new Exception('Execute sql error. ' . $ext_sql, 824209006);
			}
			return $this->getReturnValue($_resource, $this->sqlBindStd);
		} else {
			throw new Exception('Value(s) prepared to bind is not match.', 824209004);
		}
	}

	# 开启事务
	public function beginTa() {
		try {
			$this->setTransaction(true);
			$this->transErrorPool = array();
			$this->pdObj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdObj->beginTransaction(); // 开启事务
		} catch (Exception $e) {
			throw new Exception('Could not start transaction.', 824209007);
		}
	}

	# 事务内查询
	public function execTa($sql, $bind = null) {
		if (is_null($bind)) {
			return $this->exec($sql);
		} else {
			$this->prepare($sql);
			return $this->execBind($bind);
		}
	}

	# execTa方法的别名
	public function queryTa($sql, $bind = null) {
		return $this->execTa($sql, $bind);
	}

	# 关闭事务
	public function commitTa() {
		try {
			if ($this->transaction == true && count($this->transErrorPool) == 0) {
				$_result = $this->pdObj->commit();
			} else {
				$this->pdObj->rollBack();
				$_result = false;
			}
			$this->setTransaction('false');
			return $_result;
		} catch (Exception $e) {
			throw new Exception('Could not commit transaction.', 824209008);
		}
	}
}