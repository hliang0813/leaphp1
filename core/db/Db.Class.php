<?php
/*
说明：数据库操作。
功能：加载数据库配置文件。
作者：huang.liang@neusoft.com
最后更新：2012-08-09
*/

visit_limit();

class Db extends Base {
	static $db_object = NULL;
	static function Simple($config = 'default') {
		$config_ary = self::loadConfig($config);
		switch ($config_ary['db_driver']) {
			case 'mongodb':
				return self::MongoDSN($config_ary);
				break;
			case 'sqlite':
				$dsn = "{$config_ary['db_driver']}:{$config_ary['db_name']}";
				return new DataBase($dsn, $config_ary);
				break;
			default:
				$dsn = "{$config_ary['db_driver']}:host={$config_ary['db_server']};port={$config_ary['db_port']};dbname={$config_ary['db_name']};charset={$config_ary['db_charset']}";
				return new DataBase($dsn, $config_ary);
				break;
		}
	}

	static function MasterSlave($config = 'master_slave', $mode = 'master') {
		$config_ary = self::loadConfig($config);
		switch ($config_ary['db_driver']) {
			case 'mongodb':
				return self::MongoDSN($config_ary, $mode);
				break;
			default:
				return new MasterSlave($config_ary);
				break;
		}
	}

	# 初使化MongoDB
	static private function MongoDSN($config_ary, $mode = 'master') {
		$config = array(
			"{$config_ary['db_driver']}://{$config_ary['db_server']}:{$config_ary['db_port']}",
			array(
				'username' => empty($config_ary['db_user']) ? $config_ary['db_user'] : '',
				'password' => empty($config_ary['db_pass']) ? $config_ary['db_pass'] : '',
			),
		);
		list($dsn, $ext) = $config;
		$mongo = new MongoClient($dsn, $ext);
		if ($config_ary['db_name']) {
			$mongo = $mongo->$config_ary['db_name'];
		}
		if ($mode == 'slave') {
			$mongo->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);
		}
		return $mongo;
	}

	# 加载数据库配置文件
	static function loadConfig($config) {
		$config_file = realpath(CONFIG_DIR . DS . "db" . DS . $config . ".ini");
		if (file_exists($config_file)) {
			return parse_ini_file($config_file, true);
		} else {
			throw new Exception("Error on loading database configuration file.", 824209001);
		}
	}
}
