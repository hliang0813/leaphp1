<?php
visit_limit();

include_once __DIR__ . '/Predis/Autoloader.php';
Predis\Autoloader::register();

class PredisClient extends Predis\Client {
	public function __construct($config = null) {
		$redis_config_file = CONFIG_DIR . DS . 'cache' . DS . 'RedisConfig.ini';
		if (file_exists($redis_config_file)) {
			if ($config == null) {
				$config = 'redis';
			}
			$config_ary = parse_ini_file($redis_config_file, true);
			if (array_key_exists($config, $config_ary)) {
				$server_config = array();
				$server_tmp_ary = explode(',', $config_ary[$config]['servers']);
				foreach ($server_tmp_ary as $single_server) {
					list($host, $port) = explode(':', $single_server);
					$server_config[] = array(
						'host' => $host,
						'port' => $port,
					);
				}
				parent::__construct($server_config);
			} else {
				throw new Exception('Could not find the right statuement in Redis configure file.', 824209018);
			}
		} else {
			throw new Exception('Could not find Redis configure file.', 824209017);
		}
	}
}